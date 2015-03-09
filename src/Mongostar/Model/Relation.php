<?php

abstract class Mongostar_Model_Relation implements Mongostar_Model_Relation_Interface
{
	/**
	 *
	 */
	CONST DELETE_CASCADE = 'cascade';

	/**
	 *
	 */
	CONST DELETE_NONE = 'none';

	/**
	 *
	 * @var string
	 */
	protected $_relation_name;

	/**
	 *
	 * @var Model
	 */
	protected $_local_object;

	/**
	 * Delete mode. No cascade deleting by default
	 *
	 * @var string
	 */
	protected $_delete_mode = self::DELETE_NONE;

	/**
	 *
	 * @param Mongostar_Model_Interface $model
	 * @param Mongostar_Model_Reflection_Property $property
	 *
	 * @return Mongostar_Model_Relation_Interface
	 */
	public final static function factory(Mongostar_Model_Interface $model, Mongostar_Model_Reflection_Property $property)
	{
		if (!$property->isRelation()) {
			throw new Exception('Relation type is not defined');
		}

		$relation_class_name = self::_get_relation_class_name($property);
		$property_params = $property->params;

		if (!class_exists($relation_class_name))
		{
			throw new Exception("Relation class $relation_class_name is not defined");
		}

		$relation = new $relation_class_name($model, $property->type, $property_params, $property->name);

		if (isset($property_params['onDelete'])) {
			$relation->set_delete_mode($property_params['onDelete']);
		}

		return $relation;
	}

	/**
	 *
	 * @param string $delete_mode
	 * @throws Exception if unknown delete mode
	 */
	public function set_delete_mode($delete_mode)
	{
		if (!in_array($delete_mode, array(self::DELETE_CASCADE, self::DELETE_NONE))) {
			throw new Exception('Unknown delete behaviour [' . $delete_mode . ']');
		}

		$this->_delete_mode = $delete_mode;
	}

	/**
	 *
	 * @return string
	 */
	public function get_delete_mode()
	{
		return $this->_delete_mode;
	}

	/**
	 *
	 * @return bool
	 */
	public function need_cascade_deleting()
	{
		return $this->_delete_mode == self::DELETE_CASCADE;
	}

	/**
	 *
	 * @return string
	 */
	public function get_relation_name()
	{
		return $this->_relation_name;
	}

	public function set_relation_name($name)
	{
		$this->_relation_name = $name;
		return $this;
	}

	/**
	 *
	 * @param Mongostar_Model_Reflection_Property $property
	 * @return string
	 */
	protected static function _get_relation_class_name(Mongostar_Model_Reflection_Property $property)
	{
		return "Mongostar_Model_Relation_{$property->params['relation']}";
	}

	/**
	 * @param Mongostar_Model $object
	 */
	public function set_local_object(Mongostar_Model $object)
	{
		$this->_local_object = $object;
		return $this;
	}

	/**
	 * @return Mongostar_Model_Persistent
	 */
	public function get_local_object()
	{
		return $this->_local_object;
	}


	/**
	 *
	 * @param Model $model
	 * @return boolean
	 */
	protected function _isset_model_foreign_object(Model $model)
	{
		$relation = $model->get_relation($this->get_relation_name());
		return $relation->isset_foreign_object();
	}

	/**
	 *
	 * @param string $class
	 *
	 * @return Mongostar_Model_Mapper_Interface
	 */
	protected function _get_foreign_mapper($class)
	{
		return call_user_func(array($class, 'getMapper'));
	}
}