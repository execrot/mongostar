<?php

interface Mongostar_Model_Mapper_Interface
{
	/**
	 * @param Mongostar_Model_Interface $model
	 *
	 * @return mixed
	 */
	public function create(Mongostar_Model_Interface $model);

	/**
	 * @param Mongostar_Model_Interface $model
	 * @param bool $upsert
	 *
	 * @return bool
	 */
	public function update(Mongostar_Model_Interface $model, $upsert = false);

	/**
	 * @param Mongostar_Model_Interface $model
	 * @return bool
	 */
	public function delete(Mongostar_Model_Interface $model);

	/**
	 * @param array $cond
	 * @param array $sort
	 *
	 * @return Mongostar_Model
	 */
	public function fetchOne(array $cond = null, array $sort = null);

	/**
	 * @param array $cond
	 * @param array $sort
	 *
	 * @return Mongostar_Model
	 */
    public function fetchObject(array $cond = null, array $sort = null);

	/**
	 * @param array $cond
	 * @param array $sort
	 * @param null  $count
	 * @param null  $offset
	 * @param null  $hint
	 *
	 * @return Mongostar_Model_Collection
	 */
	public function fetchAll(array $cond = null, array $sort = null, $count = null, $offset = null, $hint = NULL);

	/**
	 * @param array $cond
	 * @return int
	 */
	public function getCount(array $cond = null);

	/**
	 * @return array
	 */
	public function getDecorators();

	/**
	 * @param array $decorators
	 */
	public function setDecorators(array $decorators = array());
}