<?php

namespace test\model;

use PHPUnit\Framework\TestCase;

use lola_mongo\model\AMongoResourceQuery;



final class AMongoResourceQueryTest
extends TestCase
{

	public function _callProtected(AMongoResourceQuery $ins, string $name, ...$args) {
		$method = new \ReflectionMethod(AMongoResourceQuery::class, $name);
		$method->setAccessible(true);

		return $method->invokeArgs($ins, $args);
	}


	private function _mockQuery(array $requirements = [], array $order = []) : AMongoResourceQuery {
		return $this
			->getMockBuilder(AMongoResourceQuery::class)
			->setConstructorArgs([ $requirements, $order ])
			->getMockForAbstractClass();
	}


	public function test_getPropertyMap() {
		$query = $this->_mockQuery();

		$this->assertInternalType('array', $this->_callProtected($query, '_getOperatorMap'));
	}

	public function test_setPropertyMap() {
		$query = $this->_mockQuery();
		$map = [
			1 => 'foo',
			2 => 'bar'
		];

		$this->assertEquals($query, $this->_callProtected($query, '_setOperatorMap', $map));
		$this->assertEquals($map, $this->_callProtected($query, '_getOperatorMap'));
	}

	public function test_getPropertyTransformMap() {
		$query = $this->_mockQuery();

		$this->assertInternalType('array', $this->_callProtected($query, '_getPropertyTransformMap'));
	}

	public function test_setPropertyTransformMap() {
		$query = $this->_mockQuery();
		$map = [
			1 => function($prop, $value) { return $prop; },
			2 => function($prop, $value) { return $value; }
		];

		$this->assertEquals($query, $this->_callProtected($query, '_setPropertyTransformMap', $map));
		$this->assertEquals($map, $this->_callProtected($query, '_getPropertyTransformMap'));
	}

	public function test_getValueTransformMap() {
		$query = $this->_mockQuery();

		$this->assertInternalType('array', $this->_callProtected($query, '_getValueTransformMap'));
	}

	public function test_setValueTransformMap() {
		$query = $this->_mockQuery();
		$map = [
			1 => function($value, $propId) { return $value; },
			2 => function($value, $propId) { return $propId; }
		];

		$this->assertEquals($query, $this->_callProtected($query, '_setValueTransformMap', $map));
		$this->assertEquals($map, $this->_callProtected($query, '_getValueTransformMap'));
	}

	public function test_getOperatorMap() {
		$query = $this->_mockQuery();

		$this->assertInternalType('array', $this->_callProtected($query, '_getOperatorMap'));
	}

	public function test_setOperatorMap() {
		$query = $this->_mockQuery();
		$map = [
			1 => AMongoResourceQuery::OP_EQ,
			2 => AMongoResourceQuery::OP_GT
		];

		$this->assertEquals($query, $this->_callProtected($query, '_setOperatorMap', $map));
		$this->assertEquals($map, $this->_callProtected($query, '_getOperatorMap'));
	}

	public function testGetQuery_matching() {
		$query = $this->_mockQuery([
			1 => 'baz:quux',
			2 => 'bang'
		]);

		$this->_callProtected($query, '_setPropertyMap', [
			1 => 'foo',
			2 => 'bar'
		]);

		$this->_callProtected($query, '_setPropertyTransformMap', [
			1 => function(string $prop, $value) {
				return $prop . '.' . explode(':', $value)[0];
			}
		]);

		$this->_callProtected($query, '_setValueTransformMap', [
			1 => function($value) {
				return explode(':', $value)[1];
			}
		]);

		$this->_callProtected($query, '_setOperatorMap', [
			1 => AMongoResourceQuery::OP_EQ,
			2 => AMongoResourceQuery::OP_NEQ
		]);

		$this->assertEquals([
			'$and' => [
				[ 'foo.baz' => [ '$eq' => 'quux' ]],
				[ 'bar' => [ '$ne' => 'bang' ]]
			]
		], $query->getQuery());
	}
}
