<?php

namespace Flexix\EntityAnalyzerBundle\Util;

use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Description of EntityAnalyzer
 *
 * @author Mariusz Piela <mariuszpiela@tmsolution.pl>
 */
class EntityAnalyzer {

    protected $configuration;
    protected $entityClass;

    public function __construct($configurationFilePath, $entityClass) {
        if (file_exists($configurationFilePath)) {
            $this->configuration = Yaml::parse(file_get_contents($configurationFilePath));
            $this->entityClass = $entityClass;
        } else {
            throw new \Exception(sprintf('File %s doesn\'t exists', $configurationFilePath));
        }
    }

    public function getId() {
        $ids = $this->configuration[$this->entityClass]['id'];
        foreach ($ids as $key => $value) {
            return $key;
        }
    }

    public function getEntityClass() {
        return $this->entityClass;
    }

    public function getEntity() {
        $entityClassArr = explode('\\', $this->entityClass);
        return end($entityClassArr);
    }

    public function getProperties() {

        $result = [];

        foreach ($this->configuration[$this->entityClass]['properties'] as $field => $property) {

            $type = $this->configuration[$this->entityClass]['properties'][$field]['type'];


            if ($type == 'entity') {

                $relationType = $this->configuration[$this->entityClass]['properties'][$field]['relation_type'];
                $definition = $this->configuration[$this->entityClass][$relationType][$field];
            } else {

                $definition = $this->configuration[$this->entityClass][$type][$field];
            }

            $result[$field] = array_merge($this->configuration[$this->entityClass]['properties'][$field], $definition);
        }


        return $result;
    }

    public function getFilterFields() {
        return $this->getDefinitions($this->configuration[$this->entityClass]['views']['filter']);
    }

    public function getInsertFields() {
        return $this->getDefinitions($this->configuration[$this->entityClass]['views']['insert']);
    }

    public function getEditFields() {
        return $this->getDefinitions($this->configuration[$this->entityClass]['views']['edit']);
    }

    public function getListFields() {
        return $this->getDefinitions($this->configuration[$this->entityClass]['views']['list']);
    }

    public function getSortFields() {
        return $this->getDefinitions($this->configuration[$this->entityClass]['views']['sort']);
    }

    public function getTypeaheadFields() {
        
        
        $fields=$this->getDefinitions(array_keys($this->configuration[$this->entityClass]['properties']));
        
        
       
        
        if(array_key_exists('name', $fields))
        {
             return ['name'=>$fields['name']];   
        }
        else
        {
            foreach($fields as $fieldname=>$field)
            {
                if($field['type']=='string')
                {
                    return [$fieldname =>$field];
                }    
            }
            
        }
        
        return [];
        
    }
    
    
    public function getStringField()
    {
        $arr=$this->getTypeaheadFields();
        
        foreach($arr as $key=>$value)
        {
            return $key;
        }    
        
    }
    
    

    public function getGetFields() {
        return $this->getDefinitions($this->configuration[$this->entityClass]['views']['get']);
    }

    public function getTabsFields() {

        return $this->getDefinitions(array_keys($this->configuration[$this->entityClass]['views']['tabs']['items']));
    }

    public function getTabsOverrides() {
        return $this->configuration[$this->entityClass]['views']['tabs']['overwrite'];
    }

    public function getRecord() {

        return $this->configuration[$this->entityClass]['views']['record'];
    }

    public function getDefintion($field) {
        $type = $this->configuration[$this->entityClass]['properties'][$field]['type'];

        if ($type == 'entity') {
            $relationType = $this->configuration[$this->entityClass]['properties'][$field]['relation_type'];
            $definition = $this->configuration[$this->entityClass][$relationType][$field];
        } else {
            $definition = $this->configuration[$this->entityClass][$type][$field];
        }

        return array_merge($this->configuration[$this->entityClass]['properties'][$field], $definition);
    }

    public function getDefinitions($fields) {
        $result = [];

        foreach ($fields as $field) {
            $result[$field] = $this->getDefintion($field);
        }
        return $result;
    }

    public function getRelatedFieldName($fieldName, $field, $parentEntityName/*, $targetEntityNamespace*/) {


        $entityClass = new \ReflectionClass($field['fullNamespace']);
        $annotationReader = new AnnotationReader();
        $relationType = $field['relation_type'];

        $releatedFieldName="";
        
        if (array_key_exists('inversedBy', $field) && !empty($field['inversedBy'])) {
        
            $releatedFieldName =$field['inversedBy'];
            
        } else if (array_key_exists('mappedBy', $field) && !empty($field['mappedBy'])) {
            
             $releatedFieldName =$field['mappedBy'];
        }
        

        if (!$releatedFieldName) {
            throw new \Exception(sprintf("%s class can't find releated field in class %s. Bidirectional relation needed.", ucfirst($parentEntityName), $entityClass->getName()));
            //return lcfirst($parentEntityName);
        } else {
            return $releatedFieldName;
        }
    }

}
