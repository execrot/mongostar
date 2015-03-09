<?php

abstract class Mongostar_Model_Mapper_Abstract implements Mongostar_Model_Mapper_Interface
{
	/**
	 * Model class implementing Mongostar_Model_Interface
	 * @var string
	 */
	protected $_model_class_name;

	/**
	 * Converts persistent data to entity instance
	 *
	 * @var string
	 */
	protected $_model_collection_class_name = 'Mongostar_Model_Collection';

	/**
	 * Key to use for fetching instead id
	 *
	 * @var mixed
	 */
	protected $_primary_key_name = 'id';

	/**
	 * Rules, to switch between field names in model and its storage
	 *
	 * Example:
	 *
	 * array(
	 *    array('storage' => '_id', 'model' => 'id')
	 * )
	 *
	 * @var array $_translateRules
	 */
	protected $_translate_rules = array();

	/**
	 * Auto translate from camelCase to underscore_separated
	 * if there are translate rules they will be applied instead of auto translate
	 *
	 * @var boolean
	 */
	protected $_auto_translate = false;

	/**
	 * Array with decorators names
	 *
	 * @var array $_decorators
	 */
	protected $_decorators = array();

	/**
	 * Get mapper decorators
	 *
	 * @return array
	 */
	public function getDecorators()
	{
		return $this->_decorators;
	}

	/**
	 * @param array $decorators
	 */
	public function setDecorators(array $decorators = array())
	{
		$this->_decorators = $decorators;
	}

	/**
	 * @param $data
	 * @return Mongostar_Model
	 */
	public function create_model($data)
	{
		if (null === $data || false === $data) {
			return null;
		}

		$modelClass = $this->get_model_class();
		$data = $this->translate_from_storage($data);
		$model = new $modelClass($data);
		$model->isCreated(true);

		return $model;
	}

	/**
	 * @param mixed $data
	 * @return Mongostar_Model_Collection
	 */
	public function create_model_collection($data)
	{
		$objects = array();

		foreach ($data as $key => $model) {

			if (!$model instanceof Mongostar_Model_Interface)  {
				$objects[] = $this->create_model($model);
			}

			else {
				$objects[] = $model;
			}
		}

		return new $this->_model_collection_class_name($objects);
	}

	/**
	 * @return string
	 */
	public function get_model_class()
	{
		if (empty($this->_model_class_name))
		{
			$class_name = get_class($this);
			if (strpos($class_name, '_Mapper'))
			{
				$this->set_model_class(str_replace('_Mapper', '', $class_name));
			}
		}
		return $this->_model_class_name;
	}

	/**
	 *
	 * @param string $class_name
	 *
	 * @return Mongostar_Model_Mapper_Abstract
	 */
	public function set_model_class($class_name)
	{
		$this->_model_class_name = $class_name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_primary_key_name()
	{
		return $this->_translate_key($this->_primary_key_name, false);
	}

	/**
	 *
	 * @param array $cond
	 * @param array $sort
	 *
	 * @return Mongostar_Model_Interface
	 */
	public function fetchOne(array $cond = null, array $sort = null)
	{
		return $this->fetchAll($cond, $sort, 1)->current();
	}

	/**
	 * Translate field names in data, got from storage, to model propertiy names,
	 * using rules, defined in $_translateRules
	 *
	 * @param array $data
	 * @return array
	 */
	public function translate_from_storage($data = array())
	{
		return $this->_translate($data, false);
	}

	/**
	 * Translate field names in data, got from model, to storage fields names,
	 * using rules, defined in $_translateRules
	 *
	 * @param array $data
	 * @return array
	 */
	public function translate_to_storage($data = array())
	{
		return $this->_translate($data, true);
	}

	/**
	 * @param array $data
	 * @param boolean $direction true -> model2storage, false -> storage2model
	 *
	 * @return array
	 */
	protected function _translate($data = array(), $direction = true)
	{
		if (empty($data) || (empty($this->_translate_rules) && false === $this->_auto_translate)) {
			return $data;
		}

		foreach ($data as $key => $value) {

			// search in translate rules
			$filtered_name = $this->_translate_key($key, $direction);

			// transform
			if ($key !== $filtered_name) {
				$data[$filtered_name] = $data[$key];
				unset($data[$key]);
			}
		}

		return $data;
	}

	/**
	 * @param  string $key
	 * @param  boolean $direction true -> model2storage, false -> storage2model
	 *
	 * @return string translated key
	 */
	protected function _translate_key($key, $direction = true)
	{
		// search in translate rules
		foreach ($this->_translate_rules as $rule) {

			if ($direction && $rule['model'] == $key) {
				return $rule['storage'];
			}
			elseif (!$direction && $rule['storage'] == $key) {
				return $rule['model'];
			}
		}

		return $key;
	}

	/**
	 * @return string
	 */
	public function get_model_name()
	{
		return $this->_model_class_name;
	}
}