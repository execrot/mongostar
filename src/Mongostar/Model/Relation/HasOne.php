<?php

class Mongostar_Model_Relation_HasOne extends Mongostar_Model_Relation_BelongsTo
{
	protected $_not_found_foreign_object_exception = false;
}