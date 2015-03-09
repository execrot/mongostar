<?php

class Mongostar_Model_Abstract implements Mongostar_Model_Interface
{
    /**
     * @var bool
     */
    protected $_is_created;

    /**
     * @var array
     */
    protected $_data = array();

    /**
     * @var array
     */
    protected $_relations = array();

    /**
     * @var string
     */
    protected $_class_name;

    /**
     * @var string
     */
    protected $_collection_name;

    /**
     * @var array
     */
    protected static $_relations_map = array();

    /**
     * @var array
     */
    protected $_indexes = array();

    /**
     *
     * @param bool $is_created
     *
     * @return bool
     */
    public function isCreated($is_created = null)
    {
        if ($is_created === null) {
            return $this->_is_created;
        }

        $this->_is_created = $is_created;
    }

    /**
     * @param array $data
     */
    public function __construct($data = null)
    {
        $this->_class_name = get_class($this);

        $this->_setupProperties();
        $this->_setupRelations();

        if (null !== $data) {
            $this->populate($data);
        }

        $this->set_object_to_relations();
    }

    public function _setupProperties()
    {
        $reflection = Mongostar_Model_Reflection::instance($this->_class_name);

        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            if (!$property->isRelation()) {
                $this->_data[$property->name] = $property->getDefaultValue();
            }
        }
    }

    /**
     * @throws Exception
     */
    public function _setupRelations()
    {
        if (!array_key_exists($this->_class_name, self::$_relations_map)) {
            self::$_relations_map[$this->_class_name] = array();
            $doc_block_properties = Mongostar_Model_Reflection::instance($this->_class_name)->getProperties();

            foreach ($doc_block_properties as $property) {
                // relation
                if ($property->isRelation()) {
                    $relation = Mongostar_Model_Relation::factory($this, $property);
                    if (null !== $relation) {
                        self::$_relations_map[$this->_class_name][$relation->get_relation_name()] = $relation;
                    }
                    else {
                        throw new Exception('Wrong relation definition');
                    }
                }
            }
        }
    }

    public function set_object_to_relations()
    {
        foreach (self::$_relations_map[$this->_class_name] as $name => $relation) {
            $this->_relations[$name] = clone $relation;
            $this->_relations[$name]->set_local_object($this);
        }
    }

    /**
     * @param $data
     *
     * @return $this
     * @throws Exception
     */
    public function populate($data)
    {
        if (is_object($data)) {
            if (method_exists($data, 'asArray')) {
                $data = $data->asArray();
            }
            else {
                $data = (array)$data;
            }
        }

        if (!is_array($data)) {
            throw new Exception("Can't populate data. Must be array or object.");
        }

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->_data)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
     * @param bool $forceTypes
     *
     * @return array
     */
    public function asArray($forceTypes = false)
    {
        $data = array();

        foreach ($this->_data as $key => $value) {

            $data[$key] = $this->__get($key);
            if ($forceTypes && is_object($data[$key]) && ($data[$key] instanceof MongoId)) {

                $data[$key] = (string)$data[$key];
            }
        }

        return $data;
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function __isset($property)
    {
        if (array_key_exists($property, $this->_relations)) {

            $this->_relations[$property]->set_local_object($this);

            return $this->_relations[$property]->isset_foreign_object();
        }
        elseif (method_exists($this, 'get'.ucfirst($property))) {

            return true;
        }
        else {
            return array_key_exists($property, $this->_data);
        }
    }

    /**
     * @param string $property
     *
     * @return array|mixed|null
     * @throws Exception
     */
    public function __get($property)
    {
        if (in_array($property, array_keys($this->_data))) {

            $value = $this->_data[$property];

            if (is_null($value)) {

                switch(self::getPropertyType($property))
                {
                    case 'array':
                        return array();
                }
            }

            return $this->_data[$property];
        }

        $method = array(
            $this,
            'get'.ucfirst($property)
        );

        if (is_callable($method)) {
            return call_user_func($method);
        }

        // try to get foreign object
        if (array_key_exists($property, $this->_relations)) {
            $this->_relations[$property]->set_local_object($this);

            return $this->_relations[$property]->get_foreign_object();
        }

        if (strstr($property, '.')) {
            $parts = explode('.', $property);
            $value = $this->_data;

            foreach ($parts as $part) {
                if (isset($value[$part])) {
                    $value = $value[$part];
                }
                else {
                    $value = null;
                }
            }

            return $value;
        }

        throw new Exception('Property '.$property.' does not exist');
    }

    /**
     * @param $property
     * @param $value
     *
     * @return mixed
     * @throws Exception
     */
    public function __set($property, $value)
    {
        // try set property using setter
        if (in_array($property, array_keys($this->_data))) {
            $this->_data[$property] = $value;
        }
        else {
            $method = array(
                $this,
                'set'.ucfirst($property)
            );
            if (is_callable($method)) {
                return call_user_func($method, $value);
            }

            if (array_key_exists($property, $this->_relations)) {
                $this->_relations[$property]->set_local_object($this);
                $this->_relations[$property]->set_foreign_object($value);

                return;
            }

            // check if property exists
            if (!array_key_exists($property, $this->_data)) {
                throw new Exception('Setting new entity property ('.$property.') is forbidden');
            }
        }
    }

    /**
     * Get primary key value
     */
    public function getPrimaryKeyValue()
    {
        $mapper = $this->getMapper();
        $primary_key_name = $mapper->get_primary_key_name();

        return $this->{$primary_key_name};
    }

    /**
     * @return string
     */
    protected static function _getClassName()
    {
        return get_called_class();
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public static function getPropertyType($name)
    {
        return Mongostar_Model_Reflection::instance(self::_getClassName())->getProperty($name)->type;
    }

    public function getIndexes()
    {
        return $this->_indexes;
    }
}