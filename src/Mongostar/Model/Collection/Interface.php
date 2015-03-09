<?php

interface Mongostar_Model_Collection_Interface extends Iterator, Countable, ArrayAccess
{
	/**
	 * @param $model
	 * @return mixed
	 */
	public function append($model);

	/**
	 * @param $model
	 * @return mixed
	 */
	public function prepend($model);

	/**
	 * @param $data
	 * @return mixed
	 */
	public function populate($data);

	/**
	 * @return mixed
	 */
	public function clear();

	/**
	 * @return mixed
	 */
	public function asArray();
}