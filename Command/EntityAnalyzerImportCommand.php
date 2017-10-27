<?php

/*
 * This file is part of the Doctrine Bundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project, Benjamin Eberlei <kontakt@beberlei.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flexix\EntityAnalyzerBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Flexix\EntityAnalyzerBundle\Util\Export\ClassMetadataExporter;
use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;

/**
 * Import Doctrine ORM metadata mapping information from an existing database.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Fabien Potencier <mariusz.piela@tmsolution.pl>
 *  */
class EntityAnalyzerImportCommand extends DoctrineCommand {

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this
                ->setName('flexix:entity-analyzer:import')
                ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to import the mapping information to')
                ->addArgument('module', InputArgument::REQUIRED, 'The module name')
                ->addOption('entity', null, InputOption::VALUE_OPTIONAL, 'The entity to generate')
                ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command')
                ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command')
                ->addOption('filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A string pattern used to match entities that should be mapped.')
                ->addOption('force', null, InputOption::VALUE_NONE, 'Force to overwrite existing mapping files.')
                ->setDescription('Imports mapping information from an existing database')
                ->setHelp(<<<EOT
The <info>%command.name%</info> command imports mapping information
from an existing database:

<info>php %command.full_name% "MyCustomBundle" xml</info>

You can also optionally specify which entity manager to import from with the
<info>--em</info> option:

<info>php %command.full_name% "MyCustomBundle"  --em=default</info>

If you don't want to map every entity that can be found in the database, use the
<info>--filter</info> option. It will try to match the targeted mapped entity with the
provided pattern string.

<info>php %command.full_name% "MyCustomBundle"  --filter=MyMatchedEntity</info>

Use the <info>--force</info> option, if you want to override existing mapping files:

<info>php %command.full_name% "MyCustomBundle"  --force</info>
EOT
        );
    }

    protected function getEntities($param) {
        
    }

    protected function getSimpleEntityName($className) {

        $arr = explode("\\", $className);
        return end($arr);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $bundle = $this->getApplication()->getKernel()->getBundle($input->getArgument('bundle'));

        $destPath = $bundle->getPath();
        $nameSpace = $bundle->getNamespace();
        $type = 'yml';

        $destPath .= '/Resources/config/entityAnalyze/'.$input->getArgument('module');

        $cme = new ClassMetadataExporter();
        $exporter = $cme->getExporter($type);
        $exporter->setOverwriteExistingFiles($input->getOption('force'));

        if ('annotation' === $type) {

            $entityGenerator = $this->getEntityGenerator();
            $exporter->setEntityGenerator($entityGenerator);
        }

        $em = $this->getEntityManager($input->getOption('em'), $input->getOption('shard'));

        $classNames = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();



        $databaseDriver = new DatabaseDriver($em->getConnection()->getSchemaManager());
        $em->getConfiguration()->setMetadataDriverImpl($databaseDriver);

        $emName = $input->getOption('em');
        $emName = $emName ? $emName : 'default';

        //$cmf = new DisconnectedClassMetadataFactory();
        //$cmf->setEntityManager($em);
        //$metadata = $cmf->getAllMetadata();

        $metadata = [];
        $nameSpaceStringLength = strlen($nameSpace);

$em->getMetadataFactory()->getReflectionService();
$sm=$em->getConnection()->getSchemaManager();


        $fromSchema = $sm->createSchema();
       // $toSchema = $this->getSchemaFromMetadata($classNames);
        
        foreach ($classNames as $className) {

            if (substr($className, 0, $nameSpaceStringLength) == $nameSpace) {
                
              
                $metadata[] = $em->getMetadataFactory()->getMetadataFor($className);
            }
        }


        //$metadata = MetadataFilter::filter($metadata, $input->getOption('filter'));

        if ($metadata) {

            $output->writeln(sprintf('Importing mapping information from "<info>%s</info>" entity manager', $emName));

            foreach ($metadata as $class) {

                $className = $this->getSimpleEntityName($class->rootEntityName);

                if (!$input->getOption('entity') || $input->getOption('entity') == $className) {

                    $class->name = $bundle->getNamespace() . '\\Entity\\' . $className;

                    if ('annotation' === $type) {

                        $path = $destPath . '/' . str_replace('\\', '.', $className) . '.php';
                    } else {

                        $path = $destPath . '/' . str_replace('\\', '.', $className) . '.orm.' . $type;
                    }

                    $output->writeln(sprintf('  > writing <comment>%s</comment>', $path));
                    $code = $exporter->exportClassMetadata($class);

                    if (!is_dir($dir = dirname($path))) {

                        mkdir($dir, 0775, true);
                    }


                    $this->dumpFile($path, $code);




                    chmod($path, 0664);
                }
            }

            return 0;
        } else {

            $output->writeln('Database does not have any mapping information.');
            $output->writeln('');

            return 1;
        }
    }

    public function dumpFile($filename, $content) {

        $checksumController = $this->getContainer()->get('flexix_checlsum.util.checksum_controller');
        $checksumControllerDirBuidler = $this->getContainer()->get('flexix_checlsum.util.dir_builder');


        if (file_exists($filename)) {
            if ($checksumController->checkChecksum($filename)) {
                $checksumController->addFile($filename);
            } else {
                $folderPath = $checksumControllerDirBuidler->getTempFolderPath($filename);

                if (!file_exists($folderPath)) {
                    mkdir($folderPath, 0777, true);
                }

                return file_put_contents($checksumControllerDirBuidler->getTempFilePath($filename), $content);
            }
        } else {
            $checksumController->addFile($filename);
        }

        return file_put_contents($filename, $content);
    }

}
