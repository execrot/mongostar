<?php

class Mongostar_Model_Relation_BelongsTo extends Mongostar_Model_Relation
{
	/**
	 * @var Mongostar_Model_Interface
	 */	
	protected $_foreign_object;
	
	/**
	 * @var string
	 */
	protected $_local_key;
	
	/**
	 * @var string
	 */
	protected $_foreign_key;
	
	/**
	 * @var string
	 */
	protected $_foreign_class_name;
	
	/**
	 * @var string
	 */
	protected $_local_class_name;
	
	/**
	 * @var boolean
	 */
	protected $_not_found_foreign_object_exception = true;
	
	/**
	 * 
	 * @param Mongostar_Model_Interface $model
	 * @param string $foreign_class_name
	 * @param array  $options
	 * @param string $property_name
	 */
	public function __construct(Mongostar_Model_Interface $model, $foreign_class_name, $options, $property_name)
	{		
		$this->set_relation_name($property_name);

		// Make foreign class name		
		$foreign_class_name = ucfirst($foreign_class_name);
		$this->_foreign_class_name = $foreign_class_name;
		
		// Check if foreign class exists
		if (!class_exists($foreign_class_name)) 
		{
			throw new Exception("Class $this->_foreign_class_name is not defined");
		}
		
		$this->_local_class_name = get_class($model);		
		
		// Set relation fields
		if (isset($options['localKey'])) 
		{
			$this->_local_key = $options['localKey'];
		} 
		else 
		{
			$this->_localKey = $this->_relation_name . '_id';
		}
		
		if (isset($options['foreignKey'])) 
		{
			$this->_foreign_key = $options['foreignKey'];
		} 
		else
		{
			$this->_foreign_key = $this->_get_foreign_mapper($this->_foreign_class_name)->get_primary_key_name();
		}
		
		// check if localKey exists in $model
		$exists = true;
		if (strstr($this->_local_key, '.'))
		{
			$property = substr($this->_local_key, 0, strpos($this->_local_key, '.'));
			if (!isset($model->{$property}))
			{
				$exists = false;
			}
		} elseif (!isset($model->{$this->_local_key}))
		{
			$exists = false;
		}
		if (!$exists)
		{
			throw new Exception("Model $this->_local_class_name has no property $this->_local_key");
		}
	}
	
	/**
	 * 
	 * @param array $request_params
	 * @return Mongostar_Model_Interface|null
	 */
	public function match($request_params)
	{
		if (isset($request_params[$this->_local_key])) 
		{ 
			$fetch_params = array($this->_foreign_key => $request_params[$this->_local_key]);
			$foreign_object = $this->_get_foreign_mapper($this->_foreign_class_name)->fetchOne($fetch_params);
			
			if (null === $foreign_object) 
			{
				throw new Exception("Object of $this->_foreign_class_name for matched relation $this->_relation_name not found " . implode("=", $fetch_params));
			} 
			else 
			{
				return $foreign_object;
			}
		}	
	}
	
	/**
	 * @return Mongostar_Model_Persistent
	 */
	public function get_foreign_object()
	{
		if (null === $this->_foreign_object) 
		{
			$local_value = $this->_local_object->{$this->_local_key};
			if (null !== $local_value) 
			{
				$fetch_params = array($this->_foreign_key => $local_value);
				$this->_foreign_object = $this->_get_foreign_mapper($this->_foreign_class_name)->fetchOne($fetch_params);
			}
			
			if (null === $this->_foreign_object) 
			{
				if ($this->_not_found_foreign_object_exception) 
				{
					throw new Exception("Object of $this->_foreign_class_name is not found by param '$this->_foreign_key = $local_value'");
				} 
				else 
				{
					$this->_foreign_object = false;
				}
			}
		}
		
		if (false === $this->_foreign_object) 
		{
			return null;
		}
		
		return $this->_foreign_object;
	}
	
	/**
	 * 
	 * @param Mongostar_Model_Persistent $object
	 * 
	 * @return void
	 */
	public function set_foreign_object($object)
	{
		if ($object instanceof $this->_foreign_class_name) 
		{
			$this->_foreign_object = $object;
			$this->_local_object->{$this->_local_key} = $object->{$this->_foreign_key};			  
		} 
		else 
		{
			throw new Exception("Invalid parent. Must be instance of {$this->_foreign_class_name}");
		}
	}
	
	/**
	 * @return boolean
	 */
	public function isset_foreign_object()
	{
		return (null !== $this->_foreign_object);
	}
	
	/**
	 * 
	 * @param Mongostar_Model_Collection $collection
	 */
	public function set_foreign_objects_to_collection(Mongostar_Model_Collection $collection, $refresh = false)
	{
		// collect foregn keys values
		$local_models = array();
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
					$local_models[$model->{$this->_local_key}][] = $model;
				}
			}
		}
		
		// fetch foreign models
		$fetch_params = array($this->_foreign_key => array_keys($local_models));
		$foreign_objects = $this->_get_foreign_mapper($this->_foreign_class_name)->fetchAll($fetch_params);
		
		// set foreign objects in collection models
		foreach ($foreign_objects as $foreign_object) 
		{
			foreach ($local_models[$foreign_object->{$this->_foreign_key}] as $local_model) 
			{
				$local_model->{$this->_relation_name} = $foreign_object;
			}
		}
	}
}