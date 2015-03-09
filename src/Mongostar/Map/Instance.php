<?php

class Mongostar_Map_Instance
{
    /**
     * @var array
     */
    protected static $_instances;

    /**
     * @var array
     */
    protected $_initialData;

    /**
     * @return mixed
     */
    public static function instance()
    {
        $objectName = get_called_class();

        if (!isset(self::$_instances[$objectName])) {
            self::$_instances[$objectName] = new $objectName;
        }

        return self::$_instances[$objectName];
    }

    /**
     * @param $data
     * @param $name
     * @param $context
     *
     * @return null|string
     */
    protected function _transform($data, $name, $context)
    {
        $value = null;

        $methodName = 'get'.ucfirst(strtolower($name));

        if (method_exists($this, $methodName)) {
            $value = $this->$methodName($data, $context);
        }
        elseif (is_array($data)) {
            $value = isset($data[$name]) ? $data[$name] : null;
        }
        elseif (is_object($data)) {
            $value = isset($data->{$name}) ? $data->{$name} : null;
        }

        if ($value instanceof MongoId) {
            $value = (string)$value;
        }

        return $value;
    }

    /**
     * @param $data
     * @param $context
     *
     * @return array
     * @throws Exception
     */
    protected function _executeData($data, $context)
    {
        $method = 'rules'.ucfirst($context);

        if (!method_exists($this, $method)) {
            throw new Exception('Map context rules not found. Tried to use '.$method.' method at '.get_class($this));
        }

        $rules = $this->$method();
        $result = array();

        foreach ($rules as $name => $rule) {
            $result[$rule] = $this->_transform($data, $name, $context);
        }

        return $result;
    }

    /**
     * @param        $data
     * @param string $context
     * @param array  $initialData
     *
     * @return mixed
     */
    public static function execute($data, $context = 'common', $initialData = array())
    {
        return self::instance()->_execute($data, $context, $initialData);
    }

    /**
     * @param       $data
     * @param       $context
     * @param array $initialData
     *
     * @return array|null
     */
    protected function _execute($data, $context, $initialData = array())
    {
        if (empty($data)) {
            return null;
        }

        $this->_initialData = $initialData;

        $result = array();

        if ($data instanceof Mongostar_Model_Abstract) {
            return $this->_executeData($data, $context);
        }

        foreach ($data as $row) {
            array_push($result, $this->_executeData($row, $context));
        }

        return $result;
    }
}