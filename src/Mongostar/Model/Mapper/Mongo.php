<?php

class Mongostar_Model_Mapper_Mongo extends Mongostar_Model_Mapper_Abstract
{
	/**
	 *
	 * @var string
	 */
	protected $_collection_name;

	/**
	 *
	 * @var array
	 */
	protected $_property_types = array();

	/**
	 *
	 * @var string
	 */
	protected $_mongo_instance_name = 'default';

	/**
	 * Before set this one do sommthing like this  db.Increments.insert({"_id":"Chats", "seq":new NumberLong(1)});
	 * @var boolean
	 */
	protected $_use_mongo_inc_as_auto_increment_key = NULL;

	/**
	 *
	 * @var string
	 */
	protected $_mongo_increment_collection_name = 'Increments';

	/**
	 *
	 * @var array
	 */
	protected $_translate_rules = array(
		array('storage' => '_id', 'model' => 'id')
	);

	/**
	 *
	 * @var string
	 */
	protected $_primary_key_name = '_id';

	/**
	 *
	 * @var MongoCollection
	 */
	protected static $_collections = array();

	/**
	 *
	 * @var array
	 */
	protected static $_mongos = array();

	public function __construct($model_class_name = null)
	{
		if (null === $model_class_name)
		{
			$model_class_name = $this->_model_class_name;
		}
		else
		{
			$this->_model_class_name = $model_class_name;
		}
		if (null === $model_class_name)
		{
			throw new Exception("Model class name don't writed");
		}

		$this->_setup();
	}

	protected function _setup()
	{
		if (null === $this->_collection_name)
		{
			$this->_collection_name = str_replace(array('App_Model_', 'Model_', '_'), array('',''), $this->_model_class_name);
		}

		if (null === $this->_use_mongo_inc_as_auto_increment_key)
		{
			$type = call_user_func_array(array($this->_model_class_name, 'getPropertyType'), array('id'));
			$this->_use_mongo_inc_as_auto_increment_key = (strtolower($type) != 'mongoid');
		}
	}

	/**
	 *
	 * @param string $name
	 */
	public function set_collection_name($name)
	{
		$this->_collection_name = $name;
	}

	/**
	 *
	 * @param string $instance
	 */
	public function set_mongo_instance_name($instance)
	{
		$this->_mongo_instance_name = $instance;
	}

	/**
	 *
	 * @param array $data
	 * @param array $cond
	 *
	 * @return boolean
	 */
	public function update_by_cond(array $data = array(), $cond = array())
	{
		$query = $this->_make_query($cond);
		$data = $this->_translate($data, true);

		return $this->_getCollection()->update($query['cond'], array('$set' => $data), array('multiple' => true));
	}

	/**
	 *
	 * @param array $cond
	 */
	public function delete_by_cond($cond = array())
	{
		$query = $this->_make_query($cond);
		$this->_getCollection()->remove($query['cond']);
	}

	/**
	 *
	 * @param Mongostar_Model_Interface $model
	 * @return
	 */
	public function create(Mongostar_Model_Interface $model)
	{
		$data = $model->asArray();
		$data = $this->translate_to_storage($data);
		$data = $this->_prepare($data);

		if ((empty($data[$this->_primary_key_name])))
		{
			if ($this->_use_mongo_inc_as_auto_increment_key)
			{
				$data[$this->_primary_key_name] = $this->get_next_id_from_mongo();
			}

			if (array_key_exists('id', $data))
			{
				unset($data['id']);
			}
		}
		if (null == $data[$this->_primary_key_name])
		{
			unset($data[$this->_primary_key_name]);
		}
		foreach ($data as $k => $v)
		{
			if ($v === NULL)
			{
				unset($data[$k]);
			}
		}
		$result = $this->_getCollection()->insert($data);

		if ($result)
		{
			$model->isCreated(TRUE);
			$model->id = $data[$this->_primary_key_name];
			return $model;
		}
		else
		{
			return null;
		}
	}

	/**
	 *
	 * @param  string $id
	 * @return Mongostar_Model_Interface
	 */
	public function fetch($id)
	{
		return $this->fetchOne(array('id' => $id));
	}

	/**
	 *
	 * @param Mongostar_Model_Interface $model
	 *
	 * @return Mongostar_Model_Interface
	 */
	public function update(Mongostar_Model_Interface $model, $upsert = FALSE)
	{
		$data = $model->asArray();
		$data = $this->_prepare($data);

		if (isset($data[$this->get_primary_key_name()]))
		{
			unset($data[$this->get_primary_key_name()]);
		}

		$data = $this->translate_to_storage($data);
		$unset = array();

		$cond = $this->_make_query(array($this->_primary_key_name => $model->getPrimaryKeyValue()));

		$opts = array();

		foreach ($data as $k => $v)
		{
			if ($v === NULL)
			{
				$unset[$k] = 1;
				unset($data[$k]);
			}
		}

		if ($upsert) {
			$opts['upsert'] = TRUE;
		}

		$data = array('$set' => $data);

		if ($unset) {
			$data['$unset'] = $unset;
		}

		$this->_getCollection()->update($cond['cond'], $data, $opts);

		return $model;
	}


	public function upsert(Mongostar_Model_Interface $model)
	{
		return $this->update($model, TRUE);
	}

	/**
	 *
	 * @param Mongostar_Model_Interface $model
	 * @return boolean
	 */
	public function save(Mongostar_Model_Interface $model)
	{
		if ( ! $model->isCreated())
		{
			return $this->create($model);
		}
		else
		{
			return $this->update($model);
		}
	}

	/**
	 *
	 * @param Mongostar_Model_Interface $model
	 * @return boolean
	 */
	public function delete(Mongostar_Model_Interface $model)
	{
		$cond = $this->_make_query(array(
			$this->_primary_key_name => $model->getPrimaryKeyValue()
		));

		return $this->_getCollection()->remove($cond['cond']);
	}

	/**
	 *
	 * @param array   $cond
	 * @param array   $sort
	 * @param integer $count
	 * @param integer $offset
	 *
	 * @return Mongostar_Model_Collection
	 */
	public function fetchAll(array $cond = null, array $sort = null, $count = null, $offset = null, $hint = NULL)
	{
		$query = $this->_make_query($cond, $sort, $count, $offset, $hint);

		$result = $this->_find_by_query($query);

		return $result;
	}

	/**
	 *
	 * @param array   $cond
	 * @param array   $sort
	 * @param integer $count
	 * @param integer $offset
	 *
	 * @return Mongostar_Model_Collection
	 */
	public function fetchAllData($cond = null, array $sort = null, $count = null, $offset = null, $hint = null)
	{
		$query = $this->_make_query($cond, $sort, $count, $offset, $hint);
		$result = $this->find_data_by_query($query);

		return $result;
	}

	/**
	 *
	 * @param string $field
	 * @param array $cond
	 * @param array $sort
	 * @param int $count
	 * @param int $offset
	 * @return array
	 */
	public function fetchOneField($field, $cond = null, array $sort = null, $count = null, $offset = null, $hint = null)
	{
		$result = array();
		$query = $this->_make_query($cond, $sort, $count, $offset, $hint);


		$field = $this->_translate_key($field);

		$query['fields'] = array ($field);
		$data = $this->find_data_by_query($query);
		if ($data)
		{
			foreach ($data as $item)
			{
				if (isset($item[$field]))
					array_push($result, $item[$field]);
			}
		}

		return $result;
	}

	/**
	 *
	 * @param array $cond
	 * @return integer
	 */
	public function getCount(array $cond = null)
	{
		$query = $this->_make_query($cond);
		return $this->count_by_query($query);
	}

	/**
	 * @param $query
	 * @return int
	 *
	 * @throws Exception
	 */
	public function count_by_query($query)
	{
		try {
			return $this->_getCollection()->count($query['cond']);
		}  catch(Exception $e) {
			throw new Exception(
				'Error in '.$this->_collection_name . " \n" .
				$e->getMessage() . " \n" .
				"query: \n" . var_export($query['cond'], true)
			);
		}
	}

	/**
	 *
	 * @param array $cond
	 * @param array $sort
	 *
	 * @return Mongostar_Model_Interface|NULL
	 */
	public function fetchOne(array $cond = null, array $sort = null)
	{
		$query = $this->_make_query($cond, $sort, 1, 0);

		if ($sort)
		{
			$data = $this->_find_by_query($query);
			$result = $data->current();
		}
		else
		{
			$result = $this->_find_one_by_query($query);
		}

		return $result;
	}

    /**
     * @param array $cond
     * @param array $sort
     *
     * @return Mongostar_Model_Interface
     */
    public function fetchObject(array $cond = null, array $sort = null)
    {
        $result = $this->fetchOne($cond, $sort);

        if (!$result)
        {
            $result = new $this->_model_class_name();
        }

        return $result;
    }

	/**
	 *
	 * @param Morph_Query $query
	 * @return Mongostar_Model_Collection
	 */
	protected function _find_by_query($query)
	{
		try {
			if (empty($query['fields']))
			{
				$cursor = $this->_getCollection()->find($query['cond']);
			}
			else
			{
				$cursor = $this->_getCollection()->find($query['cond'], $query['fields']);
			}

			if (!empty($query['sort']))
			{
				$cursor->sort($query['sort']);
			}

			if (!empty($query['limit']))
			{
				$cursor->limit($query['limit']);
			}

			if (!empty($query['offset']))
			{
				$cursor->skip($query['offset']);
			}

			if (!empty($query['hint']))
			{
				$cursor->hint($query['hint']);
			}

			$data = iterator_to_array($cursor);

			$cursor->reset();
		} catch(Exception $e) {
			throw new Exception(
				'Error in '.$this->_collection_name . " \n" .
					$e->getMessage() . " \n" .
					"query: \n" . var_export($query['cond'], true)
			);
		}
		return $this->create_model_collection($data);
	}

	/**
	 *
	 * @param Morph_Query $query
	 * @return Mongostar_Model_Persistent
	 */
	protected function _find_one_by_query($query)
	{
		try {
			if (empty($query['fields']))
			{
				$result = $this->_getCollection()->findOne($query['cond']);
			}
			else
			{
				$result = $this->_getCollection()->findOne($query['cond'], $query['fields']);
			}


		} catch(Exception $e) {
			throw new Exception(
				'Error in '.$this->_collection_name . " \n" .
					$e->getMessage() . " \n" .
					"query: \n" . var_export($query['cond'], true)
			);
		}
		return $this->create_model($result);
	}

	/**
	 *
	 * @param array $query
	 * @return
	 */
	protected function find_data_by_query($query)
	{
		try {
			if (empty($query['fields']))
			{
				$cursor = $this->_getCollection()->find($query['cond']);
			}
			else
			{
				$cursor = $this->_getCollection()->find($query['cond'], $query['fields']);
			}

			if (!empty($query['sort'])) {
				$cursor->sort($query['sort']);
			}

			if (!empty($query['limit'])) {
				$cursor->limit($query['limit']);
			}

			if (!empty($query['offset'])) {
				$cursor->skip($query['offset']);
			}

			if (!empty($query['hint']))
			{
				$cursor->hint($query['hint']);
			}

			$data = iterator_to_array($cursor);

			$cursor->reset();
		}  catch(Exception $e) {
			throw new Exception(
				'Error in '.$this->_collection_name . " \n" .
					$e->getMessage() . " \n" .
					"query: \n" . var_export($query['cond'], true)
			);
		}
		return $data;
	}

	/**
	 *
	 * @param array   $cond
	 * @param array   $sort
	 * @param integer $count
	 * @param integer $offset
	 *
	 */
	protected function _make_query($cond = array(), $sort = array(), $count = null, $offset = null, $hint = null)
	{
		$condition = array();
		if ($cond instanceof Mongostar_Model_Mapper_Mongo_Expression)
		{
			$condition = $cond->get_expr();
		}
		elseif (!empty($cond))
		{
			$clauses = array('$gt', '$lt', '$gte', '$lte', '$nin', '$ne', '$in');

			foreach ($cond as $key => $value)
			{
				if (is_array($value))
				{
					if (in_array(key($value), $clauses))
					{
						$condition[$key] = array();
						foreach ($value as $clause => $clause_value)
						{
							$cl = $clause;
							$val = $this->_format_value($key, $clause_value);
							if ($cl == '$nin' && gettype($val) == 'string')
							{
								$cl = '$ne';
							}
							$condition[$key][$cl] = $val;
						}
					}
					else
					{
						$condition[$key] = array('$in' => $this->_format_value($key, $value));
						// var_dump($key, $this->_format_value($key, $value), $condition[$key]);
					}
				}
				else
				{
					$condition[$key] = $this->_format_value($key, $value);
				}
			}

			$condition = $this->translate_to_storage($condition);
		}

		$sorting = array();
		if (!empty($sort))
		{
			$sort = $this->translate_to_storage($sort);
			foreach ($sort as $key => $value)
			{
				$sorting[$key] = ($value == 1) ? 1 : -1;
			}
		}

		$query = array();
		$query['cond'] = $condition;
		$query['sort'] = $sorting;

		if ($count)
		{
			$query['limit'] = $count;
		}

		if ($offset)
		{
			$query['offset'] = $offset;
		}

		if ($hint)
		{
			$query['hint'] = $hint;
		}

		return $query;
	}


	/**
	 * @return integer
	 */
	protected function get_next_id_from_mongo()
	{
		$seq = $this->_get_db()->command(
			array(
				"findandmodify" => $this->_mongo_increment_collection_name,
				"query" => array(
					"_id" => $this->_collection_name,
				),
				"update" => array('$inc' => array('seq' => 1)),
				'new' => true
			));



		if (!isset($seq['value']))
		{
			$this->setup_increment();
			return 1;
		}

		return (int) $seq['value']['seq'];
	}


	/**
	 *
	 * @param $data
	 * @return array
	 */
	protected function _prepare($data)
	{
		$formatted = array();

		foreach ($data as $key => $value)
		{
			$formatted[$key] = $this->_format_value($key, $value);
		}

		return $formatted;
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	protected function _format_value($key, $value)
	{
		$type = call_user_func_array(array($this->_model_class_name, 'getPropertyType'), array($key));

		// format array shit
		if (is_array($value))
		{
			foreach ($value as &$val)
			{
				$val = $this->_format_value($key, $val);
			}
			return $value;
		}

		if ($value instanceof MongoId)
		{
			return $value;
		}

		if ($value instanceof MongoRegex)
		{
			return $value;
		}

		if ($value === NULL)
		{
			return $value;
		}

		// convert to detected type
		switch (strtolower($type))
		{
			case 'int':
			case 'integer':
				return (int) $value;

			case 'string':
				return (string) $value;

			case 'mongoid':

				try { $mongoId = new MongoId($value); }
				catch (Exception $e) { $mongoId = new MongoId(); }

				return $mongoId;

			default:
				return $value;
		}
	}

	/**
	 *
	 * @return MongoCollection
	 */
	protected function _getCollection()
	{
		if (!isset(self::$_collections[$this->_collection_name]))
		{
			self::$_collections[$this->_collection_name] = $this->_get_db()->{$this->_collection_name};
		}

		return self::$_collections[$this->_collection_name];
	}

	/**
	 *
	 * @return MongoCollection
	 */
	public function getCollection()
	{
		return $this->_getCollection();
	}

	/**
	 * @return MongoDb
	 */
	protected function _get_db()
	{
		$instance_name = $this->_mongo_instance_name;
		$config = Mongostar_Model::getConfig()[$instance_name];
		return $this->_get_mongo()->{$config['connection']['db']};
	}

	/**
	 *
	 * @return Mongo
	 */
	protected function _get_mongo()
	{
		$instance_name = $this->_mongo_instance_name;
		$config = Mongostar_Model::getConfig()[$instance_name];
		$options = isset($config['options']) ? $config['options'] : array();
		$connection = "mongodb://".$config['connection']['server'];

		self::$_mongos[$this->_mongo_instance_name] = new MongoClient($connection, $options);

		return self::$_mongos[$this->_mongo_instance_name];
	}

	public function setup_increment()
	{
		$obj = array(
			"_id" => $this->_collection_name,
			"seq" => new MongoInt32(1)
		);

		$this->_get_db()->{$this->_mongo_increment_collection_name}->insert($obj);
	}

	/**
	 * Actually this method needed for old migrations.
	 *
	 * @return MongoDb
	 */
	public function get_connection()
	{
		return $this->_get_db();
	}
}