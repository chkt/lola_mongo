<?php

namespace lola_mongo\model\map;

use lola\model\map\IResourceMap;

use MongoDB\Collection;



abstract class AMongoResourceMap
implements IResourceMap
{

	static private function _getQueryOptions() : array {
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


	public function getList(string $key) : array {
		if (!$this->hasKey($key)) throw new \ErrorException();

		return $this->_data[$key];
	}

	public function setList(string $key, array $list) : IResourceMap {
		$test = array_values($list);

		if ($test !== $list) throw new \ErrorException();

		$this->_data[$key] = $list;
		$this->_collection->updateOne($this->_filter, [ '$set' => [ $key => $list ]]);

		return $this;
	}


	public function getSet(string $key) : array {
		if (!$this->hasKey($key)) throw new \ErrorException();

		return $this->_data[$key];
	}

	public function setSet(string $key, array $set) : IResourceMap {
		$test = array_unique(array_values($set));

		if ($test !== $set) throw new \ErrorException();

		$this->_data[$key] = $set;
		$this->_collection->updateOne($this->_filter, [ '$set' => [ $key => $set ]]);

		return $this;
	}


	public function getMap(string $key) : array {
		if (!$this->hasKey($key)) throw new \ErrorException();

		return $this->_data[$key];
	}

	public function setMap(string $key, array $map) : IResourceMap {
		if (count(array_filter(array_keys($map), 'is_string')) !== count($map)) throw new \ErrorException();

		$this->_data[$key] = $map;
		$this->_collection->updateOne($this->_filter, [ '$set' => [ $key => $map ]]);

		return $this;
	}


	public function removeKey(string $key) : IResourceMap {
		if (!$this->hasKey($key)) throw new \ErrorException();

		unset($this->_data[$key]);
		$this->_collection->updateOne($this->_filter, [ '$unset' => [ $key => null ]]);

		return $this;
	}

	public function renameKey(string $key, string $to) : IResourceMap {
		if (!$this->hasKey($key) || $this->hasKey($to)) throw new \ErrorException();

		$this->_data[$to] = $this->_data[$key];
		unset($this->_data[$key]);

		$this->_collection->updateOne($this->_filter, [ '$rename' => [ $key => $to ]]);

		return $this;
	}
}
