<?php

class Mongostar_Model_Mapper_Connection
{
	/**
	 * @var array
	 */
	protected static $_instances = array();

	/**
	 * @var string
	 */
	protected $_name = null;

	/**
	 * @var array
	 */
	protected $_config;

	/**
	 * @var bool
	 */
	protected $_connected = false;

	/**
	 * @var MongoClient
	 */
	protected $_connection;

	/**
	 * @var MongoDB
	 */
	protected $_db;

	/**
	 * @param string $name
	 */
	protected function __construct($name)
	{
		$this->_name = $name;
		$this->_config = Mongostar_Model::getConfig()[$name];
	}

	protected function __clone () {}

	public function __destruct()
	{
		$this->disconnect();
	}

	public static function instance ($name = 'default')
	{
		if (!isset(self::$_instances[$name]))
		{
			self::$_instances[$name] = new self($name);
		}

		return self::$_instances[$name];
	}

	public function connect ()
	{
		if ($this->_connection) // already connected
		{
			return;
		}

		$host = $this->_config['connection']['server'];

		if ( isset($this->_config['connection']['username']) && isset($this->_config['connection']['password']))
		{
			$host = $this->_config['connection']['username'] . ':' . $this->_config['connection']['password'] . '@' . $host;
		}

		if ( strpos($host, 'mongodb://') !== 0)
		{
			$host = 'mongodb://' . $host;
		}

		if ( ! isset($options))
		{
			$options = array();
		}

		$options['connect'] = FALSE;
		$this->_connection = new MongoClient($host, $options);

		try
		{
			$this->_connection->connect();
		}
		catch ( MongoConnectionException $e)
		{
			throw new Exception('Unable to connect to Mongo server at :hostnames',
				array(':hostnames' => $e->getMessage()));
		}

		if ( ! isset($this->_config['connection']['db']))
		{
			throw new Exception('No database specified in MangoDB Config');
		}

		$this->_db = $this->_connection->selectDB($this->_config['connection']['db']);

		return $this->_connected = TRUE;
	}

	public function disconnect ()
	{
		if ( $this->_connection)
		{
			$this->_connection->close();
		}

		$this->_db = $this->_connection = NULL;
	}

	public function lastError ()
	{
		return $this->_connected ? $this->_db->lastError() : NULL;
	}

	public function prevError ()
	{
		return $this->_connected ? $this->_db->prevError() : NULL;
	}

	public function resetError ()
	{
		return $this->_connected ? $this->_db->resetError() : NULL;
	}

	public function command (array $data)
	{
		return $this->_call('command', array(), $data);
	}

	public function execute ($code, array $args = array() )
	{
		return $this->_call('execute', array( 'code' => $code, 'args' => $args));
	}

	public function createCollection (string $name, $capped= FALSE, $size=0, $max=0 )
	{
		return $this->_call('create_collection', array(
			'name'    => $name,
			'capped'  => $capped,
			'size'    => $size,
			'max'     => $max
		));
	}

	public function dropCollection ($name)
	{
		return $this->_call('drop_collection', array(
			'name' => $name
		));
	}

	public function ensureIndex ( $collection_name, $keys, $options = array())
	{
		return $this->_call('ensure_index', array(
			'collection_name' => $collection_name,
			'keys'            => $keys,
			'options'         => $options
		));
	}

	public function batchInsert ($collection_name, array $a )
	{
		return $this->_call('batch_insert', array(
			'collection_name' => $collection_name
		), $a);
	}

	public function count( $collection_name, array $query = array() )
	{
		return $this->_call('count', array(
			'collection_name' => $collection_name,
			'query'           => $query
		));
	}

	public function findOne($collection_name, array $query = array(), array $fields = array())
	{
		return $this->_call('find_one', array(
			'collection_name' => $collection_name,
			'query'           => $query,
			'fields'          => $fields
		));
	}

	public function find($collection_name, array $query = array(), array $fields = array())
	{
		return $this->_call('find', array(
			'collection_name' => $collection_name,
			'query'           => $query,
			'fields'          => $fields
		));
	}

	public function group( $collection_name, $keys , array $initial , $reduce, array $condition= array() )
	{
		return $this->_call('group', array(
			'collection_name' => $collection_name,
			'keys'            => $keys,
			'initial'         => $initial,
			'reduce'          => $reduce,
			'condition'       => $condition
		));
	}

	public function update($collection_name, array $criteria, array $newObj, $options = array())
	{
		return $this->_call('update', array(
			'collection_name' => $collection_name,
			'criteria'        => $criteria,
			'options'         => $options
		), $newObj);
	}

	public function insert($collection_name, array $a, $options = array())
	{
		return $this->_call('insert', array(
			'collection_name' => $collection_name,
			'options'         => $options
		), $a);
	}

	public function remove($collection_name, array $criteria, $options = array())
	{
		return $this->_call('remove', array(
			'collection_name' => $collection_name,
			'criteria'        => $criteria,
			'options'         => $options
		));
	}

	public function save($collection_name, array $a, $options = array())
	{
		return $this->_call('save', array(
			'collection_name' => $collection_name,
			'options'         => $options
		), $a);
	}

	public function gridFS( $arg1 = NULL, $arg2 = NULL)
	{
		$this->_connected OR $this->connect();

		if ( ! isset($arg1))
		{
			$arg1 = isset($this->_config['gridFS']['arg1'])
				? $this->_config['gridFS']['arg1']
				: 'fs';
		}

		if ( ! isset($arg2) && isset($this->_config['gridFS']['arg2']))
		{
			$arg2 = $this->_config['gridFS']['arg2'];
		}

		return $this->_db->getGridFS($arg1,$arg2);
	}

	public function get_file(array $criteria = array())
	{
		return $this->_call('get_file', array(
			'criteria' => $criteria
		));
	}

	public function get_files(array $query = array(), array $fields = array())
	{
		return $this->_call('get_files', array(
			'query'  => $query,
			'fields' => $fields
		));
	}

	public function set_file_bytes($bytes, array $extra = array(), array $options = array())
	{
		return $this->_call('set_file_bytes', array(
			'bytes'   => $bytes,
			'extra'   => $extra,
			'options' => $options
		));
	}

	public function set_file($filename, array $extra = array(), array $options = array())
	{
		return $this->_call('set_file', array(
			'filename' => $filename,
			'extra'    => $extra,
			'options'  => $options
		));
	}

	public function remove_file( array $criteria = array(), array $options = array())
	{
		return $this->_call('remove_file', array(
			'criteria' => $criteria,
			'options'  => $options
		));
	}

	protected function _call($command, array $arguments = array(), array $values = NULL)
	{
		$this->_connected OR $this->connect();

		extract($arguments);

		$_bm_name = isset($collection_name)
			? $collection_name . '.' . $command
			: $command;

		if ( isset($collection_name))
		{
			$c = $this->_db->selectCollection($collection_name);
		}

		switch ( $command)
		{
			case 'ensure_index':
				$r = $c->ensureIndex($keys, $options);
				break;
			case 'create_collection':
				$r = $this->_db->createCollection($name,$capped,$size,$max);
				break;
			case 'drop_collection':
				$r = $this->_db->dropCollection($name);
				break;
			case 'command':
				$r = $this->_db->command($values);
				break;
			case 'execute':
				$r = $this->_db->execute($code,$args);
				break;
			case 'batch_insert':
				$r = $c->batchInsert($values);
				break;
			case 'count':
				$r = $c->count($query);
				break;
			case 'find_one':
				$r = $c->findOne($query,$fields);
				break;
			case 'find':
				$r = $c->find($query,$fields);
				break;
			case 'group':
				$r = $c->group($keys,$initial,$reduce,$condition);
				break;
			case 'update':
				$r = $c->update($criteria, $values, $options);
				break;
			case 'insert':
				$r = $c->insert($values, $options);
				break;
			case 'remove':
				$r = $c->remove($criteria,$options);
				break;
			case 'save':
				$r = $c->save($values, $options);
				break;
			case 'get_file':
				$r = $this->gridFS()->findOne($criteria);
				break;
			case 'get_files':
				$r = $this->gridFS()->find($query, $fields);
				break;
			case 'set_file_bytes':
				$r = $this->gridFS()->storeBytes($bytes, $extra, $options);
				break;
			case 'set_file':
				$r = $this->gridFS()->storeFile($filename, $extra, $options);
				break;
			case 'remove_file':
				$r = $this->gridFS()->remove($criteria, $options);
				break;
		}

		if ( isset($_bm))
		{
			Profiler::stop($_bm);
		}

		return $r;
	}
}