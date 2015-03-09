<?php

interface Mongostar_Model_Interface
{
	public function getPrimaryKeyValue();
	public function populate($data);
	public function asArray();
}