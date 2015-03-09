<?php

class Mongostar_Model_Relation_BelongsToMany extends Mongostar_Model_Relation_HasMany
{
	public function __construct(Mongostar_Model_Interface $model, $foreign_class_name, array $options, $property_name)
	{
		parent::__construct($model, $foreign_class_name, $options, $property_name);

		$local_key = $this->_local_key;
		$this->_local_key = $this->_foreign_key;
		$this->_foreign_key = $local_key;
	}
}