<?php

namespace Flexix\EntityAnalyzerBundle\Util;

use Flexix\EntityAnalyzerBundle\Util\EntityAnalyzerFactory;

class EntityAnalyzerFactory {

    protected $kernelRootDir;

    public function __construct($kernelRootDir) {

        $this->kernelRootDir = $kernelRootDir;
    }

    public function getEntityAnalyzer($entityClass,$module) {

       $configPath= $this->getConfigurationFilePath($entityClass,$module);
       return  new EntityAnalyzer($configPath, $entityClass);
    }

    protected function getProjectPath() {
        
        $kernelRootDirArr = explode('\\', $this->kernelRootDir);
        array_pop($kernelRootDirArr);
        return implode(DIRECTORY_SEPARATOR, $kernelRootDirArr);
    }

    protected function getConfigurationFilePath($entityClass,$module) {
        
        $entityClassArr = explode('\\', $entityClass);
        $className=array_pop($entityClassArr);
        array_pop($entityClassArr);
        $bundlePath = implode(DIRECTORY_SEPARATOR, $entityClassArr);
        return $this->getProjectPath().'\src\\'.$bundlePath.'\Resources\config\entityAnalyze\\'.$module.'\\'.$className.'.orm.yml';
    }

}
