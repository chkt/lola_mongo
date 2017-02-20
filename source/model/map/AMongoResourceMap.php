<?php

namespace lola_mongo\model\map;

use lola\model\map\IResourceMap;

use MongoDB\Collection;



abstract class AMongoResourceMap
implements IResourceMap
{

	static private function _getQueryOptions() {
		return [
			'typeMap' => [
				'root' => 'array',
				'document' => 'array',
				'array' => 'array'
			]
		];
	}


	private $_collection;
	private $_filter;

	private $_data;

	public function __construct(
		Collection $collection,
		array $filter
	) {
		$this->_collection = $collection;
		$this->_filter = $filter;

		$this->_data = $collection->findOne($this->_filter, self::_getQueryOptions());
	}


	public function hasKey(string $key) : bool {
		return array_key_exists($key, $this->_data);
	}


	public function getBool(string $key) : bool {
		if (!$this->hasKey($key)) throw new \ErrorException();

		return $this->_data[$key];
	}

	public function setBool(string $key, bool $value) : IResourceMap {
		$this->_data[$key] = $value;
		$this->_collection->updateOne($this->_filter, [ '$set' => [ $key => $value ]]);

		return $this;
	}


	public function getInt(string $key) : int {
		if (!$this->hasKey($key)) throw new \ErrorException();

		return $this->_data[$key];
	}

	public function setInt(string $key, int $value) : IResourceMap {
		$this->_data[$key] = $value;
		$this->_collection->updateOne($this->_filter, [ '$set' => [ $key => $value ]]);

		return $this;
	}


	public function getFloat(string $key) : float {
		if (!$this->hasKey($key)) throw new \ErrorException();

		return $this->_data[$key];
	}

	public function setFloat(string $key, float $value) : IResourceMap {
		$this->_data[$key] = $value;
		$this->_collection->updateOne($this->_filter, [ '$set' => [ $key => $value ]]);

		return $this;
	}


	public function getString(string $key) : string {
		if (!$this->hasKey($key)) throw new \ErrorException();

		return $this->_data[$key];
	}

	public function setString(string $key, string $value) : IResourceMap {
		$this->_data[$key] = $value;
		$this->_collection->updateOne($this->_filter, [ '$set' => [ $key => $value ]]);

		return $this;
	}
}
