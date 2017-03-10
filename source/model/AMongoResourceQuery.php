<?php

namespace lola_mongo\model;

use lola\model\IResourceQuery;

use lola\type\StructuredData;



abstract class AMongoResourceQuery
implements IResourceQuery
{

	const VERSION = '0.2.4';


	const MODE_NONE = 0;
	const MODE_MATCH = 1;
	const MODE_AGGREGATE = 2;

	const ORDER_ASC = 1;
	const ORDER_DESC = -1;

	const OP_EQ = 1;
	const OP_NEQ = 2;
	const OP_GT = 3;
	const OP_GTE = 4;
	const OP_LT = 5;
	const OP_LTE = 6;
	const OP_EXISTS = 7;


	/**
	 * Returns the MongoDB query operator represented by $op
	 * @param uint $op The operator
	 * @return string
	 * @throws \ErrorException if $op does not represent a supported MongoDB query operator
	 */
	static protected function _getQueryOperatorOf($op) {
		$map = [
			self::OP_EQ => '$eq',
			self::OP_NEQ => '$ne',
			self::OP_GT => '$gt',
			self::OP_GTE => '$gte',
			self::OP_LT => '$lt',
			self::OP_LTE => '$lte',
			self::OP_EXISTS => '$exists'
		];

		if (!array_key_exists($op, $map)) throw new \ErrorException();

		return $map[$op];
	}

	/**
	 * Returns the sorting direction of $dir
	 * @param int $dir
	 * @return int
	 * @throws \ErrorException of $dir is not a valid sorting direction
	 */
	static protected function _getSortingDirectionOf($dir) {
		$map = [
			self::ORDER_ASC,
			self::ORDER_DESC
		];

		if (!in_array($dir, $map)) throw new \ErrorException();

		return $dir;
	}



	private $_require = null;
	private $_order = null;

	private $_propertyMap;
	private $_opMap;
	private $_propertyTransformMap;
	private $_valueTransformMap;

	private $_query = null;
	private $_queryMode = self::MODE_NONE;

	private $_sorting = null;



	/**
	 * Creates a new instance
	 * @param array $requirements
	 * @param array $order
	 */
	public function __construct(array $requirements, array $order = []) {
		$this->_require = $requirements;
		$this->_order = $order;

		$this->_propertyMap = [];
		$this->_opMap = [];
		$this->_propertyTransformMap = [];
		$this->_valueTransformMap = [];

		$this->_query = null;
		$this->_queryMode = self::MODE_NONE;

		$this->_sorting = null;
	}


	/**
	 * Returns the property map of the instance
	 * @return array
	 */
	protected function _getPropertyMap() : array {
		return $this->_propertyMap;
	}

	/**
	 * Sets the property map of the instance
	 * @param array $map
	 * @returns AMongoResourceQuery
	 */
	protected function _setPropertyMap(array $map) : AMongoResourceQuery {
		$this->_propertyMap = $map;

		return $this;
	}


	/**
	 * Returns the property transform map of the instance
	 * @return array
	 */
	protected function _getPropertyTransformMap() : array {
		return $this->_propertyTransformMap;
	}

	/**
	 * Sets the property transform map of the instance
	 * @param array $map
	 * @return AMongoResourceQuery
	 */
	protected function _setPropertyTransformMap(array $map) : AMongoResourceQuery {
		$this->_propertyTransformMap = $map;

		return $this;
	}


	/**
	 * Returns the value map of the instance
	 * @return array
	 */
	protected function _getValueTransformMap() : array {
		return $this->_valueTransformMap;
	}

	/**
	 * Sets the value map of the instance
	 * @param array $map
	 * @returns AMongoResourceQuery
	 */
	protected function _setValueTransformMap(array $map) : AMongoResourceQuery {
		$this->_valueTransformMap = $map;

		return $this;
	}


	/**
	 * Returns the operator map of the instance
	 * @return array
	 */
	protected function _getOperatorMap() : array {
		return $this->_opMap;
	}

	/**
	 * Sets the property map of the instance
	 * @param array $map
	 */
	protected function _setOperatorMap(array $map) : AMongoResourceQuery {
		$this->_opMap = $map;

		return $this;
	}


	/**
	 * Returns the property name of the property referenced by $propId
	 * @param int $propId The query property
	 * @return string
	 * @throws \ErrorException if $propId is not in the property map
	 */
	private function _getPropertyNameOf(int $propId) : string {
		$map = $this->_propertyMap;

		if (!array_key_exists($propId, $map)) throw new \ErrorException();

		return $map[$propId];
	}


	/**
	 * Returns transformed property of $prop and $val
	 * @param int $propId The property identifier
	 * @param string $prop The name of the property
	 * @param mixed $val The property value
	 * @return string
	 */
	private function _getQueryPropertyOf(int $propId, string $prop, $val) : string {
		$map = $this->_propertyTransformMap;

		if (!array_key_exists($propId, $map)) return $prop;

		return call_user_func($map[$propId], $prop, $val);
	}

	/**
	 * Returns transformed value of $val for the property referenced by $queryProp
	 * @param int $propId The query property
	 * @param mixed $val The property value
	 * @return mixed
	 */
	private function _getQueryValueOf(int $propId, $val) {
		$map = $this->_valueTransformMap;

		if (!array_key_exists($propId, $map)) return $val;

		return call_user_func($map[$propId], $val, $propId);
	}

	/**
	 * Returns the property operator of the property referenced by $queryProp
	 * @param int $queryProp
	 * @return string
	 * @throws \ErrorException if $queryProp is not in the operator map
	 */
	protected function _getPropertyOperatorOf($queryProp) {
		$map = $this->_opMap;

		if (!array_key_exists($queryProp, $map)) throw new \ErrorException();

		return $map[$queryProp];
	}



	/**
	 * Returns the default MongoDB query expression for $condition and $test
	 * @param int $condition The query property
	 * @param mixed $test The query value
	 * @param int $mode
	 * @return array
	 */
	protected function _resolveQuery($condition, $test, & $mode) {
		$mode = self::MODE_MATCH;

		$prop = $this->_getPropertyNameOf($condition);
		$op = $this->_getPropertyOperatorOf($condition);

		$queryProp = $this->_getQueryPropertyOf($condition, $prop, $test);
		$queryOp = self::_getQueryOperatorOf($op);
		$queryValue = $this->_getQueryValueOf($condition, $test);

		return [ $queryProp => [ $queryOp => $queryValue ]];
	}

	/**
	 * Returns the default MongoDB sorting expression for $condition and $direction
	 * @param int $condition
	 * @param int $direction
	 * @return array
	 */
	protected function _resolveSorting($condition, $direction) {
		return [ $this->_getPropertyNameOf($condition) => self::_getSortingDirectionOf($direction) ];
	}

	/**
	 * Returns true if $property $operator $value is true, false otherwise
	 * @param type $property The value of the property
	 * @param type $operator The comparison operator representation
	 * @param type $value The value of the test
	 * @return bool
	 * @throws \ErrorException if $operator is not a supported operator
	 */
	protected function _resolveMatch($property, $operator, $value) {
		switch ($operator) {
			case self::OP_EQ : return $property === $value;
			case self::OP_NEQ : return $property !== $value;
			case self::OP_GT : return $property > $value;
			case self::OP_GTE : return $property >= $value;
			case self::OP_LT : return $property < $value;
			case self::OP_LTE : return $property <= $value;
			default : throw new \ErrorException();
		}
	}


	/**
	 * Returns the query requirements
	 * @return array
	 */
	public function getRequirements() {
		return $this->_require;
	}

	/**
	 * Returns the query ordering
	 * @return array
	 */
	public function getOrder() {
		return $this->_order;
	}


	/**
	 * Returns true if the query is a matching query, false otherwise
	 * @return bool
	 */
	public function isMatchingQuery() {
		if (is_null($this->_query)) $this->getQuery();

		return $this->_queryMode === self::MODE_MATCH;
	}

	/**
	 * Returns true if the query is an aggregation query, false otherwise
	 * @return bool
	 */
	public function isAggregationQuery() {
		if (is_null($this->_query)) $this->getQuery();

		return $this->_queryMode === self::MODE_AGGREGATE;
	}


	/**
	 * Returns the MongoDB query
	 * @return array
	 * @throws \ErrorException if the query is neither a matching nor an aggregation query
	 */
	public function getQuery() {
		if (is_null($this->_query)) {
			$matches = [];
			$aggregate = [];

			if (method_exists($this, '_buildQuery')) $this->_buildQuery($this->_require, $matches, $aggregate);		//LEGACY
			else {
				foreach ($this->_require as $cond => $test) {
					$mode = self::MODE_NONE;
					$ret = $this->_resolveQuery($cond, $test, $mode);

					if ($mode === self::MODE_MATCH) $matches[] = $ret;
					else if ($mode === self::MODE_AGGREGATE) $aggregate[] = $ret;
					else throw new \ErrorException();
				}
			}

			$hasMatches = !empty($matches);
			$matches = $hasMatches ? [ '$and' => $matches ] : $matches;

			if (!empty($aggregate)) {
				if ($hasMatches) array_unshift($aggregate, [ '$match' => $matches ]);

				$this->_query = $aggregate;
				$this->_queryMode = self::MODE_AGGREGATE;
			}
			else {
				$this->_query = $matches;
				$this->_queryMode = self::MODE_MATCH;
			}
		}

		return $this->_query;
	}

	/**
	 * Return the MongoDB sorting order
	 * @return array
	 */
	public function getSorting() {
		if (is_null($this->_sorting)) {
			$sorting = [];

			if (method_exists($this, '_buildSorting')) $this->_buildSorting($this->_order, $sorting);		//LEGACY
			else {
				foreach ($this->_order as $cond => $dir) {
					$ret = $this->_resolveSorting($cond, $dir);
					$prop = array_keys($ret)[0];
					$dir = array_values($ret)[0];

					$sorting[$prop] = $dir;
				}
			}

			$this->_sorting = $sorting;
		}

		return $this->_sorting;
	}


	/**
	 * Returns true if $data matches the query requirements, false otherwise
	 * @param \lola\type\StructuredData $data The test data
	 * @return bool
	 */
	public function match(StructuredData $data) {
		$require = $this->getRequirements();

		foreach ($require as $cond => $test) {
			$prop = $this->_getPropertyNameOf($cond);
			$op = $this->_getPropertyOperatorOf($cond);

			if (
				!$data->hasItem($prop) ||
				!$this->_resolveMatch($data->useItem($prop), $op, $test)
			) return false;
		}

		return true;
	}
}
