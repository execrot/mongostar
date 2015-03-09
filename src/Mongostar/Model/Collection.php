<?php

class Mongostar_Model_Collection implements Mongostar_Model_Collection_Interface
{
	/**
	 * @var string
	 */
	protected $_model_class_name = 'Model';

	/**
	 * Container for model entities
	 *
	 * @var $_data array
	 */
	protected $_data = array();

	/**
	 *
	 * @var integer
	 */
	protected $_position = 0;

	/**
	 *
	 * @var array
	 */
	protected  $_search_matches = array();

	/**
	 *
	 * @param mixed $data
	 */
	public function __construct($data = array())
	{
		$this->populate($data);
	}

	/**
	 *
	 * @param mixed $data
	 */
	public function populate($data)
	{
		if (is_object($data))
		{
			$data = method_exists($data, 'asArray') ? $data->asArray() : (array) $data;
		}

		if (!is_array($data))
		{
			throw new Exception("Can't populate data. Must be array or object.");
		}

		$this->clear();

		foreach ($data as $model)
		{
			$this[] = $model;
		}
	}

	/**
	 *
	 * @param string $class_name
	 * @return Mongostar_Model_Collection
	 */
	public function set_model_class($class_name)
	{
		$this->_model_class_name = $class_name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_model_class()
	{
		return $this->_model_class_name;
	}

	/**
	 *
	 */
	public function delete()
	{
		foreach ($this as $item) {
			$item->delete();
		}
	}

	/**
	 *
	 * @return Model
	 */
	public function get_first()
	{
		return count($this->_data) ? $this->_data[0] : NULL;
	}

	/**
	 *
	 * @return Model
	 */
	public function get_last()
	{
		return count($this->_data) ? $this->_data[count($this->_data) - 1] : NULL;
	}

	/**
	 * get difference between two collections
	 *
	 * @param Mongostar_Model_Collection $collection
	 */
	public function diff($collection)
	{
		$data = array();

		if (count($collection) && count($this))
		{
			foreach ($this->_data as $key => $original_item)
			{
				foreach ($collection as $item)
				{
					if ($original_item !== $item)
					{
						$data[]= $original_item;
					}
				}
			}
		}

		return new self($data);
	}

	/**
	 *
	 * @param array $cond
	 * @return Mongostar_Model_Collection
	 */
	public function collect_items_by_cond(array $cond = array())
	{
		$collection = new $this;

		if (count($this))
		{
			foreach ($this as $item)
			{
				$item_match = true;
				foreach ($cond as $key => $value)
				{
					if ($item->$key != $value)
					{
						$item_match = false;
					}
				}

				if ($item_match)
				{
					$collection->append($item);
				}
			}
		}

		return $collection;
	}

	/**
	 *
	 */
	public function revert()
	{
		$this->_data = array_reverse($this->_data);
	}

	/*
* Methods implements Iterator
*/

	/**
	 * @return Mongostar_Model_Interface
	 */
	public function current()
	{
		return $this->valid() ? $this->_data[$this->_position] : null;
	}

	public function next()
	{
		$this->_position++;
	}

	public function key()
	{
		return $this->_position;
	}

	public function valid()
	{
		return array_key_exists($this->_position, $this->_data);
	}

	public function rewind()
	{
		$this->_position = 0;
	}

	/**
	 * Methods implements Countable
	 */

	public function count()
	{
		return count($this->_data);
	}

	/**
	 * Methods implements ArrayAccess
	 */

	public function offsetExists($offset)
	{
		return isset($this->_data[$offset]);
	}

	public function offsetGet($offset)
	{
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}

	public function offsetSet($offset, $value)
	{
		if (!$value instanceof Mongostar_Model_Interface)
		{
			$value = new $this->_model_class_name($value);
		}

		if (is_null($offset))
		{
			$this->_data[] = $value;
		}
		else
		{
			$this->_data[$offset] = $value;
		}

		return $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->_data[$offset]);
	}

	/**
	 * @param bool $with_models
	 * @return array
	 */
	public function asArray($with_models = false, $relations = array())
	{
		if ($with_models)
		{
			$result = array();
			foreach ($this->_data as $item)
			{
				$data = $item->asArray();
				if ($relations) {
					foreach ($relations as $relation) {
						if ($item->{$relation}) {
							$data[$relation] = $item->{$relation}->asArray();
						}
					}
				}
				$result[]= $data;
			}
			return $result;
		}
		else
		{
			return $this->_data;
		}
	}

	/**
	 *
	 * @param Mongostar_Model_Interface|Mongostar_Model_Collection $data
	 */
	public function append($data)
	{
		if ($data instanceof Mongostar_Model_Interface)
		{
			$this->_data[]= $data;
		}
		elseif ($data instanceof Mongostar_Model_Collection)
		{
			foreach ($data as $item)
			{
				$this->_data[]= $item;
			}
		}
	}

	/**
	 *
	 * @param Mongostar_Model_Collection $collection
	 * @param string $key_name
	 * @return Mongostar_Model_Collection
	 */
	public function append_collection_by_key_name(Mongostar_Model_Collection $collection, $key_name = 'id')
	{
		$data = array();
		if ($this->get_model_class() == $collection->get_model_class())
		{
			if (count($collection) && count($this))
			{
				$keys_array = $this->extract_field($keyName);

				$data = $this->_data;

				foreach ($collection->_data as $key => $original_item)
				{
					if(!in_array($original_item->{$key_name}, $keys_array))
					{
						$data[] = $original_item;
					}
				}
			}
		}

		return new $this($data);
	}

	/**
	 * @return Mongostar_Model_Collection
	 */
	public function clear()
	{
		$this->_data = array();
		$this->_position = 0;
		return $this;
	}

	/**
	 *
	 * @param mixed $data
	 * @return Mongostar_Model_Collection
	 */
	public function prepend($data)
	{
		$this->_data = array_reverse($this->_data);
		$this->append($data);
		$this->_data = array_reverse($this->_data);
		return $this;
	}

	/**
	 *
	 * @param string|array|null $name
	 * @param bool
	 * @return Mongostar_Model_Collection
	 */
	public function set_relations($name = null, $refresh = false)
	{
		/* @var $relation Mongostar_Model_Relation */
		foreach ($this->_get_relations($name) as $name => $relation)
		{
			if (method_exists($relation, 'set_foreign_objects_to_collection'))
			{
				$relation->set_foreign_objects_to_collection($this, $refresh);
			}
		}
		return $this;
	}

	/**
	 *
	 * @param string|array|null $name
	 *
	 * @return array
	 */
	protected function _get_relations($name = null)
	{
		$model = $this->offsetGet(0);
		if (!$model instanceof Model)
		{
			return array();
		}

		if (null === $name)
		{
			$relations = $model->get_relations();
		}
		else
		{
			$relations = array();
			$name = (array) $name;
			foreach ($name as $n)
			{
				$relations[$n] = $model->get_relation($n);
			}
		}

		return $relations;
	}

	/**
	 *
	 * @param string  $key
	 * @param boolean $reversed
	 * @param integer $length
	 * @param integer $offset
	 * @param boolean $natural
	 *
	 * @return Mongostar_Model_Collection
	 */
	public function get_sorted($key, $reversed = false, $case_sensitive = false, $natural = false)
	{
		return $this->_get_sorted_as_number($key, $reversed, $case_sensitive, $natural);
	}

	/**
	 *
	 * @param Closure $callback
	 * @param string $key
	 * @param boolean $reversed
	 * @param integer $length
	 * @param integer $offset
	 *
	 * @return Mongostar_Model_Collection
	 */
	public function get_sorted_by_callback(Closure $callback, $key, $reversed = false)
	{
		$data = $this->asArray();

		$sign = ($reversed) ? - 1 : 1;
		usort($data, function($a, $b) use ($key, $sign, $callback)
		{
			$aa = $a->$key;
			$bb = $b->$key;
			return $callback($aa, $bb) * $sign;
		});

		return new $this($data);
	}

	/**
	 * Get collection slice
	 *
	 * @param $limit
	 * @param $offset
	 * @return Mongostar_Model_Collection
	 */
	public function get_slice($limit, $offset = 0)
	{
		$data = $this->asArray();

		$data = array_slice($data, $offset, $limit);

		return new $this($data);
	}

	/**
	 *
	 * @param array $data
	 * @param string $key
	 * @param boolean $reversed
	 * @param integer $length
	 * @param integer $offset
	 * @param boolean $case_insensitive
	 * @param boolean $natural
	 *
	 * @return array
	 */
	protected function _get_sorted_as_string($key, $reversed = false, $case_insensitive = false, $natural = false)
	{
		$callback = function($a, $b) use ($caseInsensitive, $natural)
		{
			if ($natural)
			{
				if ($caseInsensitive)
				{
					return strnatcasecmp($a, $b);
				}
				else
				{
					return strnatcmp($a, $b);
				}
			}
			else
			{
				if ($caseInsensitive)
				{
					return strcasecmp($a, $b);
				}
				else
				{
					return strcmp($a, $b);
				}
			}
		};

		return $this->get_sorted_by_callback($callback, $key, $reversed);
	}

	/**
	 *
	 * @param array $data
	 * @param unknown_type $key
	 * @param unknown_type $reversed
	 * @param unknown_type $length
	 * @param unknown_type $offset
	 */
	protected function _get_sorted_as_number($key, $reversed = false)
	{
		$callback = function($a, $b)
		{
			if ($a == $b)
			{
				return 0;
			}
			elseif ($a > $b)
			{
				return 1;
			}
			else
			{
				return -1;
			}
		};

		return $this->get_sorted_by_callback($callback, $key, $reversed);
	}


	/**
	 * @param array   $data
	 * @param string  $key
	 * @param boolean $reversed
	 * @param integer $length
	 * @param integer $offset
	 * @param boolean $natural
	 *
	 * @return array
	 */
	protected function _get_sorted(array $data, $key, $reversed = false, $length = null, $offset = 0)
	{
		$sign = ($reversed) ? - 1 : 1;
		usort($data, function($a, $b) use ($key, $sign)
		{
			$aa = $a->$key;
			$bb = $b->$key;
			if ($aa == $bb)
			{
				return 0;
			}
			elseif ($aa > $bb)
			{
				return -1 * $sign;
			}
			else
			{
				return 1 * $sign;
			}
		});
		if (null !== $length || 0 != $offset)
		{
			$data = array_slice($data, $offset, $length);
		}
		return $data;
	}

	/**
	 *
	 * @param Closure $callback
	 * @param array $data
	 * @param string $key
	 * @param boolean $reversed
	 * @param integer $length
	 * @param integer $offset
	 *
	 * @return array
	 */
	protected function _get_sorted_by_callback(Closure $callback, array $data, $key, $reversed = false, $length = null, $offset = 0)
	{
		$sign = ($reversed) ? - 1 : 1;
		usort($data, function($a, $b) use ($key, $sign, $callback)
		{
			$aa = $a->$key;
			$bb = $b->$key;
			return $callback($aa, $bb) * $sign;
		});
		if (null !== $length || 0 != $offset)
		{
			$data = array_slice($data, $offset, $length);
		}
		return $data;
	}

	/**
	 * Returns array of given model fields
	 *
	 * @param array $fields Fields to fetch
	 * @return array Result
	 */
	public function extract_fields(array $fields)
	{
		$result = array();

		foreach($this as $model)
		{
			$row = array();
			foreach($fields as $field)
			{
				$row[$field] = $model->{$field};
			}
			array_push($result, $row);
		}

		return $result;
	}

	/**
	 *
	 * @param string $field
	 * @return array
	 */
	public function collect($field)
	{
		return $this->extract_field($field);
	}

	/**
	 * Returns array of one given model fields
	 *
	 * @param mixed $field Field name
	 * @return array Result
	 */
	public function extract_field($field, $unique = false)
	{
		$fields = $this->extract_fields(array($field));

		$result = array();

		foreach($fields as $field_values)
		{
			$result[] = $field_values[$field];
		}

		if($unique)
		{
			$result = array_unique($result);
		}

		return $result;
	}

	/**
	 * Returns key => value array
	 *
	 * @param string $key_field Field to be key
	 * @param string $value_field FIeld to be value
	 * @return array $result
	 */
	public function get_pairs($key_field, $value_field)
	{
		$result = array();

		foreach($this as $model)
		{
			$result[(string)$model->{$key_field}] = $model->{$value_field};
		}

		return $result;
	}

	public function get_array_with_keys($key_field)
	{
		$result = array();

		foreach($this as $model)
		{
			$key = $model->{$key_field};
			if (is_object($key))
			{
				$key = (string)$key;
			}
			$result[$key] = $model;
		}

		return $result;
	}

	public function get_search_matches()
	{
		return $this->_search_matches;
	}


	public function set_search_matches(array $matches)
	{
		$this->_search_matches = $matches;
	}
}
