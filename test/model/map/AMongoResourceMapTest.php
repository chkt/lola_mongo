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
