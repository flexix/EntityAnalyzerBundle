<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Flexix\EntityAnalyzerBundle\Util\Export\Driver;

use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Inflector\Inflector;

/**
 * ClassMetadata exporter for Doctrine YAML mapping files.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class YamlExporter extends AbstractExporter {

    /**
     * @var string
     */
    protected $_extension = '.dcm.yml';
    protected $reflectionClass;

    protected function getReflectionClass() {
        return $this->reflectionClass;
    }

    protected function findMethodByPrefix($propertyName, $methodPrefixes, $entityName = null) {


        if (is_string($methodPrefixes)) {
            $methodPrefixes = array($methodPrefixes);
        }
        foreach ($methodPrefixes as $methodPrefix) {
            $method = $this->checkMethodExists(\sprintf('%s%s', $methodPrefix, ucfirst($propertyName)));
            if ($method !== false) {
                return $method;
            }
//            if ($entityName) {
//                
//                $method = $this->checkMethodExists(\sprintf('%s%s', $methodPrefix, $entityName));
//                if ($method !== false) {
//                    return $method;
//                }
//            }


            $method = $this->checkMethodExists(\sprintf('%s%s', $methodPrefix, Inflector::pluralize(ucfirst($propertyName))));
            if ($method !== false) {
                return $method;
            }
        }
    }

    protected function checkMethodExists($methodName) {
        $reflectionClass = $this->getReflectionClass();
        if ($reflectionClass->hasMethod($methodName) && $reflectionClass->getMethod($methodName)->isPublic()) {
            return $methodName;
        }
        return false;
    }

    public function updateVisuals(&$views, $value) {
        array_push($views['filter'], $value);
        array_push($views['list'], $value);
        array_push($views['insert'], $value);
        array_push($views['edit'], $value);
        array_push($views['get'], $value);
        array_push($views['sort'], $value);
    }

//    protected function findRealField($name, $associationMapping) {
//
//
//        if (array_key_exists($associationMapping['type'], $this->associationTypes)) {
//            $annotationName = $this->associationTypes[$associationMapping['type']];
//            $class = $this->getReflectionClass();
//            $properties = $class->getProperties();
//
//            foreach ($properties as $property) {
//
//                $annotation = $this->getAnnotationReader()->getPropertyAnnotation($property, sprintf('Doctrine\\ORM\\Mapping\\%s', $annotationName));
//
//                if ($annotation && $annotation->targetEntity == ucfirst($name)) {
//
//                    return $property->getName();
//                }
//            }
//
//            //throw new \Exception(sprintf("There is no property for  relation %s with field '%s'",$annotationName,$name));
//        }
//        return $name;
//    }

    protected function initliazeAnalyzerProperites(&$array) {

        $array['properties'] = [];
        $array['business'] = [];
        $array['views']['filter'] = [];
        $array['views']['list'] = [];
        $array['views']['get'] = [];
        $array['views']['insert'] = [];
        $array['views']['edit'] = [];
        $array['views']['sort'] = [];
        $array['views']['record'] = ['columns' => []];
        $array['views']['typeahead'] = [];
        $array['views']['tabs']['overwrite'] = ['get' => false, 'edit' => false, 'insert' => false];
        $array['views']['tabs']['items'] = [];
    }

    protected function addTabs(&$array, $properties) {

        foreach ($properties as $propertyName => $property) {

            if ($property['type'] == 'entity') {

                if (in_array($property['relation_type'], ['manyToMany', 'oneToMany'])) {

                    $array['views']['tabs']['items'][$propertyName] = ['name' => $propertyName];
                }
            }
        }
    }

    protected function addNewColumn(&$array) {

        $numberOfColumns = count($array['views']['record'] ['columns']);
        $columnName = sprintf('column_%d', $numberOfColumns + 1);
        $array['views']['record'] ['columns'][$columnName]['lines'] = [];
        return $columnName;
    }

    protected function addNewLines(&$lines, $number = 1) {

        $numberOfLines = count($lines);

        for ($i = 1; $i <= count($number); $i++) {

            $lines[sprintf('line_%d', $numberOfLines + $i)] = [];
        }
    }

    public function createRecord(&$array, $properties) {

        $columnName = $this->addNewColumn($array);
        $this->addNewLines($array['views']['record'] ['columns'][$columnName]['lines'], 3);
        $count = 0;
        $array['views']['record'] ['columns']['column_1']['lines']['line_1'] = [];
        $array['views']['record'] ['columns']['column_1']['lines']['line_2'] = [];
        $array['views']['record'] ['columns']['column_1']['lines']['line_3'] = [];

        foreach ($properties as $fieldname => $field) {

            if (!array_key_exists('relation_type', $field) or ( $field['relation_type'] != 'manyToMany' || $field['relation_type'] != 'oneToMany')) {

                if ($count == 0) {

                    $array['views']['record'] ['columns']['column_1']['lines']['line_1'][] = $fieldname;
                } else if ($count == 1) {

                    $array['views']['record'] ['columns']['column_1']['lines']['line_2'][] = $fieldname;
                } else {

                    $array['views']['record'] ['columns']['column_1']['lines']['line_3'][] = $fieldname;
                }

                $count++;
            }
        }
    }

    protected function createPropertyBlock(&$array, $name, $relation_type = null) {

        if ($relation_type) {

            $array['properties'][$name]['relation_type'] = $relation_type;
            $array['properties'][$name]['type'] = 'entity';
        } else {

            $array['properties'][$name]['type'] = 'field';
        }

        $array['properties'][$name]['setter'] = $this->findMethodByPrefix($name, ['set', 'add']);
        $array['properties'][$name]['getter'] = $this->findMethodByPrefix($name, ['get']);

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function exportClassMetadata(ClassMetadataInfo $metadata) {


        $array = [];
        $this->initliazeAnalyzerProperites($array);
        try {

            $this->reflectionClass = new \ReflectionClass($metadata->rootEntityName);
          
        } catch (\Exception $e) {

            echo sprintf("%s doesn't exist in this bundle, but exists in database. Entity configuration skipped.\n", $metadata->name);
        }

        if ($this->reflectionClass) {

            $this->setObjectInfo($metadata, $array);
            $this->analyzePropertiesInfo($this->reflectionClass, $array);
            $this->createRecord($array, $array['properties']);
            $this->addTabs($array, $array['properties']);
            return $this->yamlDump([$metadata->rootEntityName => $array], 10);
        }
    }

    protected function getClassProperties($reflectionClass) {
        $properties = array();
        try {

            do {
                $rp = array();
                /* @var $property \ReflectionProperty */
                foreach ($reflectionClass->getProperties() as $property) {

                    $rp[$property->getName()] = $property;
                }
                $properties = array_merge($rp, $properties);
            } while ($reflectionClass = $reflectionClass->getParentClass());
        } catch (\ReflectionException $e) {
            
        }
        return $properties;
    }

    protected function analyzePropertiesInfo($reflectionClass, &$array) {

        $classPrefix = $this->getClassPrefix($reflectionClass->getName());
        $properties = $this->getClassProperties($reflectionClass);




        foreach ($properties as $property) {

            //$name = lcfirst(Inflector::singularize($property->getName()));
            $name = lcfirst($property->getName());
            $annotations = $this->getAnnotationReader()->getPropertyAnnotations($property);

            foreach ($annotations as $annotation) {

                $annotationClass = get_class($annotation);

                if ($annotationClass == 'Doctrine\ORM\Mapping\Column') {

                    $this->createFieldBlock($property, $annotation, $array, $classPrefix);
                } else if (in_array($annotationClass, [
                            'Doctrine\ORM\Mapping\ManyToOne',
                            'Doctrine\ORM\Mapping\ManyToMany',
                            'Doctrine\ORM\Mapping\OneToOne',
                            'Doctrine\ORM\Mapping\OneToMany'
                        ])) {

                    $relationType = $this->getRelationTypeFromClassname($annotationClass);

                    $this->createAssociatedFieldBlock($relationType, $property, $annotation, $array, $classPrefix);

                    if (in_array($annotationClass, [
                                'Doctrine\ORM\Mapping\ManyToOne',
                                'Doctrine\ORM\Mapping\OneToOne',
                                'Doctrine\ORM\Mapping\ManyToMany'
                            ])) {


                        $this->updateVisuals($array['views'], $name);
                    } else {

                        array_push($array['views']['filter'], $name);
                    }

                    $this->createPropertyBlock($array, $name, $relationType);
                }
            }
        }
    }

    protected function getRelationTypeFromClassname($annotationClass) {

        $annotationClassArray = explode('\\', $annotationClass);
        return lcfirst(end($annotationClassArray));
    }

    protected function isIdField($property) {

        $annotation = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id');

        if ($annotation) {

            return true;
        }
    }

    protected function getMappingArray($property, $mapping) {

        $mappingArray = (array) $mapping;
        $mappingArray["name"] = $property->getName();
        return $mappingArray;
    }

    protected function getClassPrefix($className) {
        $nameArray = explode('\\', $className);
        array_pop($nameArray);
        return implode('\\', $nameArray) . '\\';
    }

    protected function hasFullNamespace($className) {

        if (strpos($className, '\\') == true) {
            return true;
        }
    }

    protected function setFullNamespace(&$mappingArray, $classPrefix, $className) {

        if (!$this->hasFullNamespace($className)) {

            $mappingArray['fullNamespace'] = sprintf('%s%s', $classPrefix, $className);
        } else {
            $mappingArray['fullNamespace'] = $className;
        }
    }

    protected function setAlias(&$mappingArray, $className) {

        $classNameArr = explode('\\', $className);
        $text = str_replace('_', '-', end($classNameArr));
        $mappingArray['alias'] = ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '-$0', $text)), '-');
    }

    protected function createIndex($name, &$array) {

        if (!array_key_exists($name, $array)) {

            $array[$name] = [];
        }
    }

    protected function createFieldBlock($property, $mapping, &$array) {

        $mappingArray = $this->getMappingArray($property, $mapping);
        $name = $property->getName();

        if ($this->isIdField($property)) {

            $this->createIndex('id', $array);
            $array['id'][$name] = $this->getMappingArray($property, $mapping);
        } else {

            $this->createIndex('field', $array);
            $array['field'][$name] = $this->getMappingArray($property, $mapping);
            $this->createPropertyBlock($array, $name);
            $this->updateVisuals($array['views'], $name);
        }
    }

    protected function createAssociatedFieldBlock($relationType, $property, $mapping, &$array, $classPrefix) {

        $mappingArray = $this->getMappingArray($property, $mapping);
        $mappingArray['relation_type'] = $relationType;
        $this->setAssociationParameters($relationType, $mappingArray, $property, $classPrefix);
        $this->createIndex($relationType, $array);
        $array[$relationType][$property->getName()] = $mappingArray;
    }

    protected function getJoinColumns($property) {

        $joinColumns = [];

        $joinTable = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinTable');

        if ($joinTable) {

            $joinColumns = $joinTable->joinColumns;
        }

        $joinColumn = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumn');

        if ($joinColumn) {

            $joinColumns[] = $joinColumn;
        }

        $joinColumnsAnnotation = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumns');

        if ($joinColumnsAnnotation) {

            $joinColumns = $joinColumnsAnnotation->value;
        }

        return $joinColumns;
    }

    protected function setAssociationParameters($relationType, &$mappingArray, $property, $classPrefix) {

        $this->setFullNamespace($mappingArray, $classPrefix, $mappingArray["targetEntity"]);
        $this->setAlias($mappingArray, $mappingArray["targetEntity"]);
        $joinColumns = $this->getJoinColumns($property);


        if ($joinColumns) {

            $joinColumn = $joinColumns[0];


            if ($relationType == 'manyToMany') {

                $mappingArray['unique'] = $this->checkManyToManyUnique($joinColumn, $property);
                $mappingArray['nullable'] = true; //(information in doctrine, has no sense for generation)
            } else {

                $mappingArray['unique'] = $joinColumn->unique;
                $mappingArray['nullable'] = $joinColumn->nullable;
            }
        }
    }

    protected function checkManyToManyUnique($joinColumn, $property) {

        $joinTable = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinTable');

        if ($joinTable) {

            $inverseJoinColumns = $joinTable->inverseJoinColumns;

            if ($inverseJoinColumns) {
                return ($inverseJoinColumns[0]->unique || $joinColumn->unique);
            }
        }

        return $joinColumn->unique;
    }

    protected function setObjectInfo($metadata, &$array) {

        if ($metadata->isMappedSuperclass) {

            $array['type'] = 'mappedSuperclass';
        } else {

            $array['type'] = 'entity';
        }

        $array['table'] = $metadata->table['name'];

        if (isset($metadata->table['schema'])) {

            $array['schema'] = $metadata->table['schema'];
        }

        $inheritanceType = $metadata->inheritanceType;

        if ($inheritanceType !== ClassMetadataInfo::INHERITANCE_TYPE_NONE) {

            $array['inheritanceType'] = $this->_getInheritanceTypeString($inheritanceType);
        }

        if ($column = $metadata->discriminatorColumn) {

            $array['discriminatorColumn'] = $column;
        }

        if ($map = $metadata->discriminatorMap) {

            $array['discriminatorMap'] = $map;
        }

        if ($metadata->changeTrackingPolicy !== ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT) {

            $array['changeTrackingPolicy'] = $this->_getChangeTrackingPolicyString($metadata->changeTrackingPolicy);
        }

        if (isset($metadata->table['indexes'])) {

            $array['indexes'] = $metadata->table['indexes'];
        }

        if ($metadata->customRepositoryClassName) {

            $array['repositoryClass'] = $metadata->customRepositoryClassName;
        }

        if (isset($metadata->table['uniqueConstraints'])) {

            $array['uniqueConstraints'] = $metadata->table['uniqueConstraints'];
        }

        if (isset($metadata->table['options'])) {

            $array['options'] = $metadata->table['options'];
        }

        $array['field'] = [];
    }

    /**
     * Dumps a PHP array to a YAML string.
     *
     * The yamlDump method, when supplied with an array, will do its best
     * to convert the array into friendly YAML.
     *
     * @param array   $array  PHP array
     * @param integer $inline [optional] The level where you switch to inline YAML
     *
     * @return string A YAML string representing the original PHP array
     */
    protected function yamlDump($array, $inline = 2) {
        return Yaml::dump($array, $inline);
    }

}
