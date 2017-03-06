<?php

namespace test\model\map;

use PHPUnit\Framework\TestCase;

use MongoDB\Collection;
use lola_mongo\model\map\AMongoResourceMap;



final class AMongoResourceMapTest
extends TestCase
{
	private function _mockCollection(array& $findOneResult = [], array& $updates = [], array $match = ['foo' => 'bar']) {
		$collection = $this
			->getMockBuilder(Collection::class)
			->disableOriginalConstructor()
			->getMock();

		$collection
			->expects($this->any())
			->method('findOne')
			->with()
			->willReturnCallback(function(array $filter, array $options = []) use ($match, & $findOneResult) {
				if ($filter !== $match) throw new \ErrorException();

				return $findOneResult;
			});

		$collection
			->expects($this->any())
			->method('updateOne')
			->with()
			->willReturnCallback(function(array $filter, array $update, array $options = []) use ($match, & $updates) {
				if ($match !== $filter) throw new \ErrorException();

				$updates = $update;
			});

		return $collection;
	}


	private function _mockResource(Collection $collection, array $filter = ['foo' => 'bar']) : AMongoResourceMap {
		return $this
			->getMockBuilder(AMongoResourceMap::class)
			->setConstructorArgs([$collection, $filter])
			->getMockForAbstractClass();
	}


	public function testHasKey() {
		$props = [
			'foo' => 'baz',
			'bar' => true,
			'quux' => 1
		];

		$collection = $this->_mockCollection($props);
		$resource = $this->_mockResource($collection);

		$this->assertTrue($resource->hasKey('foo'));
		$this->assertTrue($resource->hasKey('bar'));
		$this->assertFalse($resource->hasKey('baz'));
	}

	public function testGetBool() {
		$props = [
			'foo' => true,
			'bar' => false
		];

		$collection = $this->_mockCollection($props);
		$resource = $this->_mockResource($collection);

		$this->assertTrue($resource->getBool('foo'));
		$this->assertFalse($resource->getBool('bar'));
	}

	public function testSetBool() {
		$props = [
			'foo' => true
		];
		$updates = [];

		$collection = $this->_mockCollection($props, $updates);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($resource, $resource->setBool('foo', false));
		$this->assertEquals([ '$set' => [ 'foo' => false ]], $updates);
		$this->assertEquals($resource, $resource->setBool('bar', true));
		$this->assertEquals([ '$set' => [ 'bar' => true ]], $updates);
		$this->assertTrue($resource->getBool('bar'));
	}

	public function testGetInt() {
		$props = [
			'foo' => 0,
			'bar' => 1
		];

		$collection = $this->_mockCollection($props);
		$resource = $this->_mockResource($collection);

		$this->assertEquals(0, $resource->getInt('foo'));
		$this->assertEquals(1, $resource->getInt('bar'));
	}

	public function testSetInt() {
		$props = [
			'foo' => 0
		];
		$updates = [];

		$collection = $this->_mockCollection($props, $updates);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($resource, $resource->setInt('foo', 1));
		$this->assertEquals([ '$set' => [ 'foo' => 1 ]], $updates);
		$this->assertEquals($resource, $resource->setInt('bar', 2));
		$this->assertEquals([ '$set' => [ 'bar' => 2 ]], $updates);
		$this->assertEquals(2, $resource->getInt('bar'));
	}

	public function testGetFloat() {
		$props = [
			'foo' => 0.1,
			'bar' => 0.2
		];

		$collection = $this->_mockCollection($props);
		$resource = $this->_mockResource($collection);

		$this->assertEquals(0.1, $resource->getFloat('foo'));
		$this->assertEquals(0.2, $resource->getFloat('bar'));
	}

	public function testSetFloat() {
		$props = [
			'foo' => 0.0
		];
		$updates = [];

		$collection = $this->_mockCollection($props, $updates);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($resource, $resource->setFloat('foo', 0.1));
		$this->assertEquals([ '$set' => [ 'foo' => 0.1 ]], $updates);
		$this->assertEquals($resource, $resource->setFloat('bar', 0.2));
		$this->assertEquals([ '$set' => [ 'bar' => 0.2 ]], $updates);
		$this->assertEquals(0.2, $resource->getFloat('bar'));
	}

	public function testGetString() {
		$props = [
			'foo' => 'baz',
			'bar' => 'quux'
		];

		$collection = $this->_mockCollection($props);
		$resource = $this->_mockResource($collection);

		$this->assertEquals('baz', $resource->getString('foo'));
		$this->assertEquals('quux', $resource->getString('bar'));
	}

	public function testSetString() {
		$props = [
			'foo' => ''
		];
		$updates = [];

		$collection = $this->_mockCollection($props, $updates);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($resource, $resource->setString('foo', 'baz'));
		$this->assertEquals([ '$set' => [ 'foo' => 'baz' ]], $updates);
		$this->assertEquals($resource, $resource->setString('bar', 'quux'));
		$this->assertEquals([ '$set' => [ 'bar' => 'quux' ]], $updates);
		$this->assertEquals('quux', $resource->getString('bar'));
	}


	public function testGetList() {
		$list0 = ['bar', 'baz', 'bar'];
		$list1 = ['bang', 'quux'];

		$props = [
			'foo' => $list0,
			'bar' => $list1
		];

		$collection = $this->_mockCollection($props);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($list0, $resource->getList('foo'));
		$this->assertEquals($list1, $resource->getList('bar'));
	}

	public function testSetList() {
		$list0 = ['foo', 'bar', 'foo'];
		$list1 = ['baz', 'quux', 'bang'];

		$props = [
			'foo' => ''
		];
		$updates = [];

		$collection = $this->_mockCollection($props, $updates);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($resource, $resource->setList('foo', $list0));
		$this->assertEquals(['$set' => ['foo' => $list0]], $updates);
		$this->assertEquals($resource, $resource->setList('bar', $list1));
		$this->assertEquals(['$set' => ['bar' => $list1]], $updates);
		$this->assertEquals($list1, $resource->getList('bar'));
	}

	public function testSetList_invalid_string_key() {
		$collection = $this->_mockCollection();
		$resource = $this->_mockResource($collection);

		$this->expectException(\ErrorException::class);

		$resource->setList('foo', [ 'foo' => 'bar' ]);
	}

	public function testSetList_invalid_index_key() {
		$collection = $this->_mockCollection();
		$resource = $this->_mockResource($collection);

		$this->expectException(\ErrorException::class);

		$resource->setList('foo', [ 1 => 'bar', 0 => 'foo' ]);
	}


	public function testGetSet() {
		$set0 = [ 'foo', 'bar', 'baz' ];
		$set1 = [ 'bar', 'baz', 'quux' ];

		$props = [
			'foo' => $set0,
			'bar' => $set1
		];

		$collection = $this->_mockCollection($props);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($set0, $resource->getMap('foo'));
		$this->assertEquals($set1, $resource->getMap('bar'));
	}

	public function testSetSet() {
		$set0 = [ 'foo', 'bar', 'baz' ];
		$set1 = [ 'bar', 'baz', 'quux' ];

		$props = [
			'foo' => ''
		];
		$updates = [];

		$collection = $this->_mockCollection($props, $updates);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($resource, $resource->setSet('foo', $set0));
		$this->assertEquals([ '$set' => [ 'foo' => $set0 ]], $updates);
		$this->assertEquals($resource, $resource->setSet('bar', $set1));
		$this->assertEquals([ '$set' => [ 'bar' => $set1 ]], $updates);
		$this->assertEquals($set1, $resource->getSet('bar'));
	}

	public function testSetSet_invalid_string_key() {
		$collection = $this->_mockCollection();
		$resource = $this->_mockResource($collection);

		$this->expectException(\ErrorException::class);

		$resource->setSet('foo', [ 'foo' => 'bar']);
	}

	public function testSetSet_invalid_index_key() {
		$collection = $this->_mockCollection();
		$resource = $this->_mockResource($collection);

		$this->expectException(\ErrorException::class);

		$resource->setSet('foo', [ 1 => 'foo', 0 => 'bar']);
	}

	public function testSetSet_invalid_duplicate_value() {
		$collection = $this->_mockCollection();
		$resource = $this->_mockResource($collection);

		$this->expectException(\ErrorException::class);

		$resource->setSet('foo', ['foo', 'foo', 'bar']);
	}


	public function testGetMap() {
		$map0 = ['foo' => 'bar', 'baz' => 'quux'];
		$map1 = ['bar' => 'baz', 'quux' => 'foo'];

		$props = [
			'foo' => $map0,
			'bar' => $map1
		];

		$collection = $this->_mockCollection($props);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($map0, $resource->getMap('foo'));
		$this->assertEquals($map1, $resource->getMap('bar'));
	}

	public function testSetMap() {
		$map0 = ['foo' => 'bar', 'baz' => 'quux'];
		$map1 = ['bar' => 'baz', 'quux' => 'foo'];

		$props = [
			'foo' => ''
		];
		$updates = [];

		$collection = $this->_mockCollection($props, $updates);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($resource, $resource->setMap('foo', $map0));
		$this->assertEquals(['$set' => [ 'foo' => $map0 ]], $updates);
		$this->assertEquals($resource, $resource->setMap('bar', $map1));
		$this->assertEquals(['$set' => [ 'bar' => $map1 ]], $updates);
		$this->assertEquals($map1, $resource->getMap('bar'));
	}

	public function testSetMap_invalid_no_key() {
		$collection = $this->_mockCollection();
		$resource = $this->_mockResource($collection);

		$this->expectException(\ErrorException::class);

		$resource->setMap('foo', ['bar', 'baz']);
	}

	public function testSetMap_invalid_index_key() {
		$collection = $this->_mockCollection();
		$resource = $this->_mockResource($collection);

		$this->expectException(\ErrorException::class);

		$resource->setMap('foo', [ 0 => 'foo', 1 => 'bar' ]);
	}

	public function testSetMap_invalid_mixed_key() {
		$collection = $this->_mockCollection();
		$resource = $this->_mockResource($collection);

		$this->expectException(\ErrorException::class);

		$resource->setMap('foo', [ 'bar' => 'baz', 1 => 'quux' ]);
	}


	public function testRemoveKey() {
		$props = [
			'foo' => 1,
			'bar' => 2
		];
		$updates = [];

		$collection = $this->_mockCollection($props, $updates);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($resource, $resource->removeKey('foo'));
		$this->assertEquals([ '$unset' => [ 'foo' => null ]], $updates);
		$this->assertEquals($resource, $resource->removeKey('bar'));
		$this->assertEquals([ '$unset' => [ 'bar' => null ]], $updates);
		$this->assertFalse($resource->hasKey('bar'));
	}

	public function testRenameKey() {
		$props = [
			'foo' => 1,
			'bar' => 2
		];
		$updates = [];

		$collection = $this->_mockCollection($props, $updates);
		$resource = $this->_mockResource($collection);

		$this->assertEquals($resource, $resource->renameKey('foo', 'baz'));
		$this->assertEquals([ '$rename' => [ 'foo' => 'baz' ]], $updates);
		$this->assertEquals($resource, $resource->renameKey('bar', 'quux'));
		$this->assertEquals([ '$rename' => [ 'bar' => 'quux' ]], $updates);
		$this->assertFalse($resource->hasKey('bar'));
		$this->assertTrue($resource->hasKey('quux'));
	}
}
