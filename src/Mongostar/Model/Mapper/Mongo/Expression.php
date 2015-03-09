<?php

class Mongostar_Model_Mapper_Mongo_Expression
{
	public $expr;

	/**
	 * @param null $expr
	 */
	public function __construct ($expr = null)
	{
		if ($expr !== NULL) {
			$this->set_expr($expr);
		}
	}

	/**
	 * @param $expr
	 */
	public function set_expr ($expr)
	{
		$this->expr = $expr;
	}

	/**
	 * @return mixed
	 */
	public function get_expr ()
	{
		return $this->expr;
	}
}