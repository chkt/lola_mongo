<?php

namespace lola_mongo\model\collection;

use lola\model\collection\IResourceCollection;
use lola_mongo\model\AMongoResource;

use MongoDB\Collection;
use lola\type\StructuredData;
use lola\type\query\IDataQuery;
use lola\model\IResource;
use lola\model\IResourceQuery;
use lola\model\ProxyResourceDriver;
use lola\model\ProxyResource;
use lola_mongo\model\AMongoResourceQuery;



abstract class AMongoResourceCollection
implements IResourceCollection
{

	protected $_collection = null;
	protected $_deserialize = null;
	protected $_driver = null;

	protected $_data = null;
	protected $_resource = null;

	protected $_update = null;
	protected $_updateNum = 0;

	protected $_length = 0;
	protected $_life = 0;


	public function __construct(Collection $collection) {
		$this->_collection = $collection;
		$this->_deserialize = AMongoResource::getDefaultDeserialization();
		$this->_driver = new ProxyResourceDriver();

		$this->_data = null;
		$this->_resource = null;

		$this->_update = null;
		$this->_updateNum = 0;

		$this->_length = 0;
		$this->_life = self::STATE_NEW;
	}


	private function& _produceResource($index) {
		$ins = new ProxyResource($this->_driver);
		$data = new StructuredData($this->_data[$index]);

		$ins->create($data);

		$this->_driver->addUpdateListener($ins, function(array $data) use ($index) {
			$state =& $this->_update;

			if (!array_key_exists($index, $state)) $this->_updateNum += 1;

			$state[$index] = 'update';
		});

		$this->_driver->addDeleteListener($ins, function() use ($index) {
			$state =& $this->_update;

			if (!array_key_exists($index, $state)) $this->_updateNum += 1;

			$state[$index] = 'delete';
		});

		return $ins;
	}


	public function isLive() : bool {
		return $this->_life === self::STATE_LIVE;
	}

	public function isDirty() : bool {
		return $this->_updateNum !== 0;
	}


	public function read(IResourceQuery $query, int $limit, int $offset = 0) : IResourceCollection {
		if (
			!($query instanceof AMongoResourceQuery) ||
			!is_int($limit) || $limit < 0 ||
			!is_int($offset) || $offset < 0 ||
			$this->_life !== self::STATE_NEW
		) throw new \ErrorException();

		$this->_life = self::STATE_DEAD;

		$options = [
			'typeMap' => $this->_deserialize,
			'sort' => $query->getSorting(),
			'limit' => $limit,
			'skip' => $offset
		];

		if ($query->isMatchingQuery()) $cursor = $this->_collection->find($query->getQuery(), $options);
		else $cursor = $this->_collection->aggregate($query->getQuery(), $options);

		if (!is_null($cursor)) {
			$data = $cursor->toArray();

			$this->_data = $data;
			$this->_resource = [];

			$this->_update = [];

			$this->_length = count($data);
			$this->_life = self::STATE_LIVE;
		}

		return $this;
	}


	public function update() : IResourceCollection {
		if ($this->_life !== self::STATE_LIVE) throw new \ErrorException();

		if ($this->_updateNum === 0) return $this;

		$state = $this->_update;

		$update = array_filter($this->_data, function($key) use ($state) {
			return array_key_exists($key, $state) && $state[$key] === 'update';
		}, ARRAY_FILTER_USE_KEY);

		$delete = array_filter($this->_data, function($key) use ($state) {
			return array_key_exists($key, $state) && $state[$key] === 'delete';
		}, ARRAY_FILTER_USE_KEY);

		foreach ($delete as $document) $this->_collection->deleteOne([
			'_id' => [ '$eq' => $document['_id']]
		]);

		foreach ($update as $document) $this->_collection->replaceOne([
			'_id' => [ '$eq' => $document['_id']]
		], $document);

		return $this;
	}


	public function getLength() : int {
		if ($this->_life !== self::STATE_LIVE) throw new \ErrorException();

		return $this->_length;
	}


	public function getIndexOf(IDataQuery $query) : int {
		for ($i = 0, $l = $this->_length; $i < $l; $i += 1) {
			if ($query->match($this->_data[$i])) return $i;
		}

		return -1;
	}


	public function& useItem(int $index) : IResource {
		if (
			!is_int($index) || $index < 0 ||
			$this->_life !== self::STATE_LIVE
		) throw new \ErrorException();

		if ($index > $this->_length - 1) return null;

		$resource =& $this->_resource;

		if (!array_key_exists($index, $resource)) $resource[$index] =& $this->_produceResource($index);

		return $resource[$index];
	}
}
