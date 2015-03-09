<?php
interface Mongostar_Model_Relation_Interface
{
	/**
	 * @return Mongostar_Model_Interface
	 */
	public function get_foreign_object();

	/**
	 * @return boolean
	 */
	public function isset_foreign_object();

	/**
	 *
	 * @param Mongostar_Model_Interface $object
	 */
	public function set_foreign_object($object);

	/**
	 * @return Mongostar_Model_Interface
	 */
	public function get_local_object();

	/**
	 *
	 * @param Mongostar_Model $object
	 */
	public function set_local_object(Mongostar_Model $object);

	/**
	 * @return string
	 */
	public function get_relation_name();

	/**
	 *
	 * @param string $name
	 */
	public function set_relation_name($name);

	/**
	 *
	 * @param  array $requestParams
	 * @return Mongostar_Model_Interface
	 */
	public function match($requestParams);

	/**
	 *
	 * @param Mongostar_Model_Collection $collection
	 * @param boolean $refresh
	 */
	public function set_foreign_objects_to_collection(Mongostar_Model_Collection $collection, $refresh = false);
}