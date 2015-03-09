<?php

/**
 * @method static int getCount(array $cond = null)
 */
class Mongostar_Model extends Mongostar_Model_Abstract
{
    /**
     * @var array
     */
    protected static $_config = array();

    /**
     * @var array
     */
    protected static $_mappers = array();

    /**
     * @var string
     */
    protected static $_mapperClass = 'Mongostar_Model_Mapper_Mongo';

    /**
     * @return array
     */
    public static function getConfig()
    {
        return self::$_config;
    }

    /**
     * @param array $config
     */
    public static function setConfig($config)
    {
        self::$_config = $config;
    }

    /**
     * @param Mongostar_Model_Mapper_Interface $mapper
     *
     * @throws Exception
     */
    public static function setMapper(Mongostar_Model_Mapper_Interface $mapper)
    {
        $decorators = $mapper->getDecorators();
        $mapper->setDecorators($decorators);

        if (!empty($decorators)) {

            foreach ($decorators as $decorator) {

                if (class_exists($decorator)) {
                    $mapper = new $decorator($mapper);
                }
                else {
                    throw new Exception("Mapper decorator $decorator not found");
                }
            }
        }
        self::$_mappers[self::_getClassName()] = $mapper;
    }

    /**
     * @return Mongostar_Model_Mapper_Mongo
     * @throws Exception
     */
    public static function getMapper()
    {
        $className = self::_getClassName();

        if (empty(self::$_mappers[$className])) {

            $mapperClass = static::getMapperClassName();

            $mapper = new $mapperClass($className);
            static::setMapper($mapper);
        }

        return self::$_mappers[$className];
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function getMapperClassName()
    {
        $class_name = self::_getClassName();

        if (null !== static::$_mapperClass) {
            $mapper_class = static::$_mapperClass;
        }
        else {
            $mapper_class = $class_name.'_Mapper';
        }

        if (!class_exists($mapper_class)) {
            throw new Exception("Mapper class $mapper_class does not exist");
        }

        return $mapper_class;
    }

    /**
     * Clear mappers
     */
    public static function clearMappers()
    {
        self::$_mappers = array();
    }

    /**
     * @return bool
     */
    public function save()
    {
        return self::getMapper()->save($this);
    }

    /**
     * @return bool
     */
    public function upsert()
    {
        return (boolean)self::getMapper()->upsert($this);
    }

    /**
     * @return bool
     */
    public function delete()
    {
        return (bool)self::getMapper()->delete($this);
    }

    /**
     * @param array $cond
     */
    public static function remove(array $cond = array())
    {
        self::getMapper()->delete_by_cond($cond);
    }

    /**
     * @param string $methodName
     * @param array $params
     *
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic($methodName, $params)
    {
        $method = array(self::getMapper(), $methodName);

        if (is_callable($method)) {
            return call_user_func_array($method, $params);
        }

        throw new Exception('Unable to call mapper method '.$methodName);
    }
}