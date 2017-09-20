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

    protected $associationTypes = [
        "1" => "OneToOne",
        "2" => "ManyToOne",
        "4" => "OneToMany",
        "8" => "ManyToMany"
    ];

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

    protected function findRealField($name, $associationMapping) {


        if (array_key_exists($associationMapping['type'], $this->associationTypes)) {
            $annotationName = $this->associationTypes[$associationMapping['type']];
            $class = $this->getReflectionClass();
            $properties = $class->getProperties();

            foreach ($properties as $property) {

                $annotation = $this->getAnnotationReader()->getPropertyAnnotation($property, sprintf('Doctrine\\ORM\\Mapping\\%s', $annotationName));

                if ($annotation && $annotation->targetEntity == ucfirst($name)) {
                   
                    return $property->getName();
                }
            }

            //throw new \Exception(sprintf("There is no property for  relation %s with field '%s'",$annotationName,$name));
        }
        return $name;
    }

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
            
            if(!array_key_exists('relation_type', $field) or !( $field['relation_type']!='manyToMany' || $field['relation_type']!='oneToMany') )
            {    
            
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

    protected function updateOneToMany($name, $property, &$array) {

        $oneToMany = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToMany');

        if ($oneToMany) {

            $relation = 'oneToMany';
            $array['oneToMany'][$name] = (array) $oneToMany;
            $array['properties'][$name]['relation_type'] = 'oneToMany';
            $array['properties'][$name]['type'] = 'entity';
            $array['properties'][$name]['setter'] = $this->findMethodByPrefix($name, ['set', 'add']);
            $array['properties'][$name]['getter'] = $this->findMethodByPrefix($name, ['get']);
        }
    }

    protected function updateManyToMany($name, $property, &$array) {

        $manyToMany = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToMany');

        if ($manyToMany) {

            $relation = 'manyToMany';
            $manyToMany = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToMany');
            $joinTable = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinTable');

            if (array_key_exists($relation, $array) &&
                    array_key_exists($name, $array[$relation]) &&
                    array_key_exists('joinTable', $array[$relation][$name]) &&
                    is_array($array[$relation][$name]['joinTable']) &&
                    array_key_exists('joinColumns', $array[$relation][$name]['joinTable'])
            ) {

                $count = 0;

                foreach ($joinTable->joinColumns as $joinColumn) {

                    $mergedArray = array_merge($array[$relation][$name]['joinTable']['joinColumns'][$count], (array) $joinColumn);
                    $array[$relation][$name]['joinTable']['joinColumns'][$count] = $mergedArray;

                    if ($mergedArray) {
                        
                        if (array_key_exists('unique', $mergedArray)) {

                            $array[$relation][$name]['unique'] = $mergedArray['unique'];
                        } else {

                            $array[$relation][$name]['unique'] = null;
                        }

                        if (array_key_exists('nullable', $mergedArray)) {

                            $array[$relation][$name]['nullable'] = $mergedArray['nullable'];
                        } else {

                            $array[$relation][$name]['unique'] = null;
                        }
                    }

                    $count++;
                }
            }
        }
    }

    protected function updateManyToOne($name, $property, &$array) {

        $oneToMany = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToOne');

        if ($oneToMany) {

            $relation = 'manyToOne';
            $joinColumns = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumns');

            if ($joinColumns) {
                
                if (array_key_exists($relation, $array) &&
                        array_key_exists($name, $array[$relation]) &&
                        array_key_exists('joinColumns', $array[$relation][$name]) &&
                        (
                
                                is_array($array[$relation][$name]['joinColumns']) ||
                                is_object($array[$relation][$name]['joinColumns'])
                        
                                )) {


                    $count = 0;

                    foreach ($joinColumns as $joinColumn) {

                        if (is_array($joinColumn)) {
                            
                            $joinColumn = $joinColumn[0];
                        }

                        $mergedArray = array_merge($array[$relation][$name]['joinColumns'], (array) $joinColumn);
                        $array[$relation][$name]['joinColumns'] = $mergedArray;

                        if ($mergedArray) {
                            
                            if (array_key_exists('unique', $mergedArray)) {

                                $array[$relation][$name]['unique'] = $mergedArray['unique'];

                                } else {

                                $array[$relation][$name]['unique'] = null;
                            }

                            if (array_key_exists('nullable', $mergedArray)) {

                                $array[$relation][$name]['nullable'] = $mergedArray['nullable'];
                                
                            } else {

                                $array[$relation][$name]['unique'] = null;
                            }
                        }
                        $count++;
                    }
                }
            }
        }
    }

    protected function updateOneToOne($name, $property, &$array) {

        $oneToOne = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToOne');

        if ($oneToOne) {

            $relation = 'oneToOne';
            $joinColumn = $this->getAnnotationReader()->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumn');
            
            if ($joinColumn) {


                if (array_key_exists($relation, $array) &&
                        array_key_exists($name, $array[$relation]) &&
                        array_key_exists('joinColumn', $array[$relation][$name]) &&
                        (
                        is_array($array[$relation][$name]['joinColumn']) ||
                        is_object($array[$relation][$name]['joinColumn'])
                        )) {

                    ;
                    $count = 0;

                    foreach ($joinColumns as $joinColumn) {

                        if (is_array($joinColumn)) {
                            $joinColumn = $joinColumn[0];
                        }

                        $mergedArray = array_merge($array[$relation][$name]['joinColumn'], (array) $joinColumn);
                        $array[$relation][$name]['joinColumn'] = $mergedArray;

                        if ($mergedArray) {
                            if (array_key_exists('unique', $mergedArray)) {


                                $array[$relation][$name]['unique'] = $mergedArray['unique'];
                            } else {
                                $array[$relation][$name]['unique'] = null;
                            }

                            if (array_key_exists('nullable', $mergedArray)) {

                                $array[$relation][$name]['nullable'] = $mergedArray['nullable'];
                            } else {
                                $array[$relation][$name]['unique'] = null;
                            }
                        }
                        $count++;
                    }
                }
            }
        }
    }

    protected function getRelationFromAnnotations(&$array) {
        $class = $this->getReflectionClass();
        $properties = $class->getProperties();
        foreach ($properties as $property) {
            //  try {
            //$reflectionProp = new \ReflectionProperty($class, $property);

            $name = lcfirst(Inflector::singularize($property->getName()));
            $this->updateOneToMany($name, $property, $array);
            $this->updateManyToMany($name, $property, $array);
            $this->updateManyToOne($name, $property, $array);
            $this->updateOneToOne($name, $property, $array);
            // } catch (\Exception $e) {
            // }
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
            $this->reflectionClass = new \ReflectionClass($metadata->name);
        } catch (\Exception $e) {
            echo sprintf("%s doesn't exist in this bundle, but exists in database. Entity configuration skipped.\n", $metadata->name);
        }

//        var_dump($metadata);

        if ($this->reflectionClass) {


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

            $fieldMappings = $metadata->fieldMappings;
            $ids = [];
            
            foreach ($fieldMappings as $name => $fieldMapping) {

                $fieldMapping['column'] = $fieldMapping['columnName'];
                unset($fieldMapping['columnName'], $fieldMapping['fieldName']);

                if ($fieldMapping['column'] == $name) {
                    
                    unset($fieldMapping['column']);
                }

                if (isset($fieldMapping['id']) && $fieldMapping['id']) {
                    
                    $ids[$name] = $fieldMapping;
                    unset($fieldMappings[$name]);
                    continue;
                }

                $fieldMappings[$name] = $fieldMapping;
                $this->createPropertyBlock($array,$name);
                $this->updateVisuals($array['views'], $name);

                if ($fieldMapping['type'] == 'string') {
                    
                    array_push($array['views']['typeahead'], $name);
                }
            }

            if (!$metadata->isIdentifierComposite && $idGeneratorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
                
                $ids[$metadata->getSingleIdentifierFieldName()]['generator']['strategy'] = $idGeneratorType;
            }

            $array['id'] = $ids;

            if ($fieldMappings) {
                
                if (!isset($array['field'])) {
                    
                    $array['field'] = [];
                }
                
                $array['field'] = array_merge($array['field'], $fieldMappings);
            }


            foreach ($metadata->associationMappings as $name => $associationMapping) {

                
                
              // $name=$this->findRealField($name, $associationMapping);
               echo $name."\n";
                $cascade = [];

                if ($associationMapping['isCascadeRemove']) {

                    $cascade[] = 'remove';
                }

                if ($associationMapping['isCascadePersist']) {

                    $cascade[] = 'persist';
                }

                if ($associationMapping['isCascadeRefresh']) {

                    $cascade[] = 'refresh';
                }

                if ($associationMapping['isCascadeMerge']) {

                    $cascade[] = 'merge';
                }

                if ($associationMapping['isCascadeDetach']) {

                    $cascade[] = 'detach';
                }
                if (count($cascade) === 5) {

                    $cascade = ['all'];
                }

                $associationMappingArray = [
                    'targetEntity' => $associationMapping['targetEntity'],
                    'cascade' => $cascade,
                ];

                if (isset($associationMapping['fetch'])) {

                    $associationMappingArray['fetch'] = $this->_getFetchModeString($associationMapping['fetch']);
                }

                if (isset($mapping['id']) && $mapping['id'] === true) {

                    $array['id'][$name]['associationKey'] = true;
                }

                if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {

                    $joinColumns = $associationMapping['isOwningSide'] ? $associationMapping['joinColumns'] : [];
                    $newJoinColumns = [];

                    foreach ($joinColumns as $joinColumn) {

                        $newJoinColumns[$joinColumn['name']]['referencedColumnName'] = $joinColumn['referencedColumnName'];

                        if (isset($joinColumn['onDelete'])) {

                            $newJoinColumns[$joinColumn['name']]['onDelete'] = $joinColumn['onDelete'];
                        }
                    }

                    $oneToOneMappingArray = [
                        'mappedBy' => $associationMapping['mappedBy'],
                        'inversedBy' => $associationMapping['inversedBy'],
                        'joinColumns' => $newJoinColumns,
                        'orphanRemoval' => $associationMapping['orphanRemoval'],
                    ];

                    $associationMappingArray = array_merge($associationMappingArray, $oneToOneMappingArray);

                    if ($associationMapping['type'] & ClassMetadataInfo::ONE_TO_ONE) {

                        $array['oneToOne'][$name] = $associationMappingArray;
                        $this->createPropertyBlock($array,$name,'oneToOne');
                        $this->updateVisuals($array['views'], $name);
                        
                    } else {
                        
                        $array['manyToOne'][$name] = $associationMappingArray;
                        $array['properties'][$name]['relation_type'] = 'manyToOne';
                        $this->createPropertyBlock($array,$name,'manyToOne');
                        $this->updateVisuals($array['views'], $name);
                    }
                } elseif ($associationMapping['type'] == ClassMetadataInfo::ONE_TO_MANY) {
                    
                    $oneToManyMappingArray = [
                        'mappedBy' => $associationMapping['mappedBy'],
                        'inversedBy' => $associationMapping['inversedBy'],
                        'orphanRemoval' => $associationMapping['orphanRemoval'],
                        'orderBy' => isset($associationMapping['orderBy']) ? $associationMapping['orderBy'] : null
                    ];

                    $associationMappingArray = array_merge($associationMappingArray, $oneToManyMappingArray);
                    $array['oneToMany'][$name] = $associationMappingArray;
                    $this->createPropertyBlock($array,$name,'oneToMany');
                   

//                $this->updateVisuals($array['views'],$name);
                } elseif ($associationMapping['type'] == ClassMetadataInfo::MANY_TO_MANY) {
                 
                    
                    $manyToManyMappingArray = [
                        'mappedBy' => $associationMapping['mappedBy'],
                        'inversedBy' => $associationMapping['inversedBy'],
                        'joinTable' => isset($associationMapping['joinTable']) ? $associationMapping['joinTable'] : null,
                        'orderBy' => isset($associationMapping['orderBy']) ? $associationMapping['orderBy'] : null
                    ];

                    
                    $associationMappingArray = array_merge($associationMappingArray, $manyToManyMappingArray);
                    $array['manyToMany'][$name] = $associationMappingArray;
                    $this->createPropertyBlock($array,$name,'manyToMany');
                    $array['views']['filter'][] = $name;
                    $array['views']['insert'][] = $name;
                    $array['views']['edit'][] = $name;

                }
            }

            if (isset($metadata->lifecycleCallbacks)) {

                $array['lifecycleCallbacks'] = $metadata->lifecycleCallbacks;
            }

            //$this->getRelationFromAnnotations($array);
            $this->createRecord($array, $array['properties']);
            $this->addTabs($array, $array['properties']);

            return $this->yamlDump([$metadata->name => $array], 10);
        }
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
