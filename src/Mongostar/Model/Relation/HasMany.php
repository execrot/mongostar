<?php

class Mongostar_Model_Relation_HasMany extends Mongostar_Model_Relation
{
	/**
	 * 
	 * @var string
	 */
	protected $_foreign_key;
	
	/**
	 *
	 * @var array
	 */
	protected $_foreign_sort = array();
	
	/**
	 * 
	 * @var string
	 */
	protected $_local_key;
	
	/**
	 * 
	 * @var Mongostar_Model_Collection
	 */
	protected $_foreign_objects;
	
	/**
	 * 
	 * @var string
	 */
	protected $_foreign_class_name;
	
	/**
	 * 
	 * @var stringh
	 */
	protected $_local_class_name;
	
	/**
	 * 
	 * @param Mongostar_Model_Interface $model
	 * @param string  $foreign_class_name
	 * @param array   $options
	 * @param string  $property_name
	 */
	public function __construct(Mongostar_Model_Interface $model, $foreign_class_name, array $options, $property_name)
	{	   
		$this->_foreign_class_name = $foreign_class_name;
		$this->set_relation_name($property_name);
		
		$this->_local_class_name = get_class($model);
		
		if (isset($options['foreignKey'])) 
		{
			$this->_foreign_key = $options['foreignKey'];	
		} 
		else 
		{
			$this->_foreign_key = $property_name . '_id';
		}
		
		if (isset($options['foreignSort'])) 
		{
			$sort_key = $options['foreignSort'];
			$sort_dir = 1;
			if (strpos(':', $sort_key)) 
			{
				$sort_key = explode(':', $sort_key);
				$sort_dir = $sort_key[1];
				$sort_key = $sort_key[0];
				$sort_dir = (int)str_ireplace(array('asc', 'desc'), array(0, 1), $sort_dir);
			}
			$this->_foreign_sort[$sort_key] = $sort_dir;
		}
		
		if (isset($options['localKey'])) 
		{
			$this->_local_key = $options['localKey'];	
		} 
		else 
		{
			$this->_localKey = 'id';
		}
	}
	
	/**
	 * 
	 * @param $request_params
	 */
	public function match($request_params)
	{
		return null;
	}
	
	/**
	 * @return Mongostar_Model_Collection
	 */
	public function get_foreign_object()
	{
		if (null === $this->_foreign_objects) 
		{
			$foreign_object = new $this->_foreign_class_name;
			$foreign_mapper = $foreign_object->getMapper();
			$params = $this->_local_object->{$this->_local_key};
			if ($params) 
			{
				$fetch_params = array($this->_foreign_key => $params);
				$this->_foreign_objects = $foreign_mapper->fetchAll($fetch_params, $this->_foreign_sort);
			} 
			else 
			{
				$this->_foreign_objects = new Mongostar_Model_Collection;
			}
		}
		
		return $this->_foreign_objects;
	}
	
	/**
	 * 
	 * @param Mongostar_Model_Collection $objects
	 */
	public function set_foreign_object($objects)
	{
		$this->_foreign_objects = $objects;
	}

	/**
	 * @return boolean
	 */
	public function isset_foreign_object()
	{
		return (null !== $this->_foreign_objects);
	}
	
	/**
	 * 
	 * @param Mongostar_Model_Collection $collection
	 * @param boolean $refresh
	 */
	public function set_foreign_objects_to_collection(Mongostar_Model_Collection $collection, $refresh = false)
	{
		// collect foregn keys values
		$local_models = array();
		$fetch_values = array();
		foreach ($collection as $model) 
		{
			if (!$model instanceof $this->_local_class_name) 
			{
				throw new Exception("Invalid local object. Must be instance of {$this->_local_class_name}");
			}
			// check that key is set in model
			if (isset($model->{$this->_local_key}))
			{
				if ($refresh || ! $this->_isset_model_foreign_object($model)) 
				{
					$values = (array) $model->{$this->_local_key};
					$local_models[] = array(
						'model'  => $model,
						'values' => $values,
					);
					$fetch_values = array_merge($fetch_values, $values);
				}
			}
		}
		
		// fetch foreign models
		$fetch_params = array(
			$this->_foreign_key => array_values(array_unique($fetch_values))
		);
		$foreign_objects = $this->_get_foreign_mapper($this->_foreign_class_name)->fetchAll($fetch_params);
		
		// get collection class
		$foreign_collection_class = get_class($foreign_objects);
		
		foreach ($local_models as $local_model)
		{
			// set foreign objects in collection models
			$foreign_collection = new $foreign_collection_class();
			foreach ($foreign_objects as $foreign_object) 
			{
				if (in_array($foreign_object->{$this->_foreign_key}, $local_model['values'])) 
				{
					$foreign_collection->append($foreign_object);
				}
			}
			
			$local_model['model']->{$this->_relation_name} = $foreign_collection;
		}
	}
}