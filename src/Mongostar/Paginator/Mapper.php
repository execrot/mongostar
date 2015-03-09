<?php

class Mongostar_Paginator_Mapper implements Zend_Paginator_Adapter_Interface
{
	/**
	 * @var Mongostar_Model_Mapper_Interface|null
	 */
	private $_mapper = null;

	/**
	 * @var array|null
	 */
	private $_cond = null;

	/**
	 * @var array|null
	 */
	private $_sort = null;

	/**
	 * If this parameter will be specify (by calling setDataMapper) data will be mapped
	 *
	 * @var array|null
	 */
	private $_dataMapper = null;

	/**
	 * Initialize mapper and condition properties
	 *
	 * @param Mongostar_Model_Mapper_Interface $mapper
	 * @param array $cond
	 * @param array $sort
	 */
	public function __construct (Mongostar_Model_Mapper_Interface $mapper, array $cond = null, array $sort = null)
	{
		$this->_mapper = $mapper;
		$this->_cond = $cond;
		$this->_sort = $sort;
	}

	/**
	 * Return collection of selected items
	 *
	 * @param int $offset
	 * @param int $limit
	 * @return array|Mongostar_Model_Collection
	 */
	public function getItems ($offset, $limit)
	{
		$data = $this->_mapper->fetchAll($this->_cond, $this->_sort, $limit, $offset);

		if (is_array($this->_dataMapper)) {

			return $this->_dataMapper['instance']->execute(
				$data,
				$this->_dataMapper['context'],
				$this->_dataMapper['initialData']
			);
		}

		return $data;
	}

	/**
	 * @return int
	 */
	public function count () {

		return $this->_mapper->getCount(
			$this->_cond,
			$this->_sort
		);
	}

	/**
	 * @param array $mapper
	 * @throws Exception
	 */
	public function setDataMapper(array $mapper)
	{
		if (empty($mapper['instance']) || !is_object($mapper['instance'])) {
			throw new Exception('Mapper should be specified');
		}

		$this->_dataMapper = array(
			'instance' => $mapper['instance'],
			'context' => !empty($mapper['context'])?$mapper['context']:'common',
			'initialData' => !empty($mapper['initialData'])?$mapper['initialData']:array(),
		);
	}
}