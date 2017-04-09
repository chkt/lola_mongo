<?php

namespace test\model\collection;

use PHPUnit\Framework\TestCase;

use MongoDB\Collection;
use lola\type\query\ADataQuery;
use lola_mongo\model\AMongoResourceQuery;
use lola_mongo\model\collection\AMongoResourceCollection;


final class AMongoResourceCollectionTest
extends TestCase
{

	private function _mockCursor(array $items) : \stdClass {
		$cursor = $this
			->getMockBuilder(\stdClass::class)
			->disableOriginalConstructor()
			->setMethods(['toArray'])
			->getMock();

		$cursor
			->expects($this->any())
			->method('toArray')
			->with()
			->willReturn($items);

		return $cursor;
	}

	private function _mockCollection(array $findResult = []) : Collection {
		$collection = $this
			->getMockBuilder(Collection::class)
			->disableOriginalConstructor()
			->getMock();

		$collection
			->expects($this->any())
			->method('find')
			->with($this->isType('array'), $this->isType('array'))
			->willReturn($this->_mockCursor($findResult));

		return $collection;
	}

	private function _mockCollectionQuery() : AMongoResourceQuery {
		$query = $this
			->getMockBuilder(AMongoResourceQuery::class)
			->disableOriginalConstructor()
			->getMock();

		$query
			->expects($this->any())
			->method('isMatchingQuery')
			->with()
			->willReturn(true);

		$query
			->expects($this->any())
			->method('getQuery')
			->with()
			->willReturn([]);

		return $query;
	}

	private function _mockResource(Collection& $collection) : AMongoResourceCollection {
		return $this
			->getMockBuilder(AMongoResourceCollection::class)
			->setConstructorArgs([$collection])
			->getMockForAbstractClass();
	}

	private function _mockDataQuery(array $props = [], array $ops = []) : ADataQuery {
		return $this
			->getMockBuilder(ADataQuery::class)
			->setConstructorArgs([ $props, $ops ])
			->getMockForAbstractClass();
	}


	public function testGetIndex() {
		$collection = $this->_mockCollection([[
			'foo' => 1,
			'bar' => 2,
			'baz' => 3
		], [
			'foo' => 2,
			'bar' => 2
		], [
			'foo' => 3
		]]);

		$resource = $this
			->_mockResource($collection)
			->read($this->_mockCollectionQuery(), PHP_INT_MAX);

		$query = $this->_mockDataQuery(['foo','bar','baz']);

		$this->assertEquals($resource->getIndexOf($query->setRequirements([1])), 0);
		$this->assertEquals($resource->getIndexOf($query->setRequirements([2])), 1);
		$this->assertEquals($resource->getIndexOf($query->setRequirements([1, 1])), -1);
		$this->assertEquals($resource->getIndexOf($query->setRequirements([1 => 2])), 0);
		$this->assertEquals($resource->getIndexOf($query->setRequirements([2 => 3])), 0);
	}
}
