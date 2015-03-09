<?php

class Mongostar_Model_Reflection
{
    /**
     * @var array
     */
    protected static $_instances = array();

    /**
     * @var string
     */
    protected $_className;

    /**
     * @var array
     */
    protected $_properties = array();

    /**
     * @var ReflectionClass
     */
    protected $_reflectionClass;

    /**
     * @static
     *
     * @param $className
     *
     * @return Mongostar_Model_Reflection
     */
    public static function instance($className)
    {
        if (!isset(self::$_instances[$className])) {
            self::$_instances[$className] = new self($className);
        }

        return self::$_instances[$className];
    }

    /**
     * @param $className
     *
     * @throws Exception
     */
    protected function __construct($className)
    {

        if (!class_exists($className)) {
            throw new Exception($className.' not exists');
        }

        $this->_className = $className;
        $this->_reflectionClass = new ReflectionClass($className);

        $this->_init();
    }

    protected function _init()
    {
        $docComment = $this->_reflectionClass->getDocComment();
        $rows = explode("\n", $docComment);

        foreach ($rows as $row) {
            if (preg_match('/\@property/ui', $row)) {
                $property = new Mongostar_Model_Reflection_Property($row);
                if ($property->name) {
                    $this->_properties[$property->name] = $property;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->_properties;
    }

    /**
     * @param string $name
     *
     * @return Mongostar_Model_Reflection_Property
     */
    public function getProperty($name)
    {
        return isset($this->_properties[$name]) ? $this->_properties[$name] : new Mongostar_Model_Reflection_Property('');
    }
}