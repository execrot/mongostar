<?php

class Mongostar_Model_Reflection_Property
{
	public $name;

	public $type;

	public $default;

	public $params;

	public function __construct($code)
	{
		$this->_parse($code);
	}

	protected function _parse($code)
	{
		$line = trim($code, ' *');

		if(preg_match("/@property\s+([^\s]+)\s+\\$(\w+)(\s*\=\s*\"?[^\"]+\"|\s*\=\s*[^\s]+|[^\s]*)\s*(.*)$/ui", $line, $matches))
		{
			$this->type = $matches[1];
			$this->name = $matches[2];
			$this->default = trim($matches[3], "= \"");
			$this->params = $this->_parseParams(trim($matches[4], ' []'));
		}
	}

	protected function _parseParams($paramsString)
	{
		$params = array();
		preg_match_all('/(\w+)\s*\=\s*([^\;]+)\;?/ui', $paramsString, $matches);
		if (count($matches) == 3)
		{
			foreach ($matches[1] as $i => $key)
			{
				$value = $matches[2][$i];
				$params[$key] = $value;
				preg_match_all("/([\w\.]+)\s*(\([^\)]+\)|[^\,]*)/ui", $value, $valueMatches);

				if (count($valueMatches) == 3)
				{
					if (count($valueMatches[1]) == 1 && empty($valueMatches[2][0]))
					{
						continue;
					}

					$value = array();

					foreach ($valueMatches[1] as $valueId => $valueKey)
					{
						$valueParams = $valueMatches[2][$valueId];

						if ( ! empty($valueParams))
						{
							$valueParams = trim($valueParams, ' ()');
							$valueParams = explode(',', $valueParams);
						}
						$value[$valueKey] = $valueParams;
					}
				}

				$params[$key] = $value;
			}
		}

		return $params;
	}

	public function isRelation()
	{
		return isset($this->params['relation']);
	}

	public function getDefaultValue()
	{
		if ($this->default === '')
		{
			return NULL;
		}

		switch($this->type)
		{
			case "int":
			case "integer":
				return (int)$this->default;
			case "string":
				return (string)$this->default;
			case "array":
				return is_array($this->default) ? $this->default : array();
			default:
				return NULL;
		}
	}

}