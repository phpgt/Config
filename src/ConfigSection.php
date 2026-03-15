<?php
namespace Gt\Config;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Gt\TypeSafeGetter\NullableTypeSafeGetter;
use IteratorAggregate;
use Traversable;

/**
 * @implements ArrayAccess<string, string>
 * @implements IteratorAggregate<string, string>
 */
class ConfigSection implements ArrayAccess, IteratorAggregate {
	use NullableTypeSafeGetter;

	protected string $name;
	/** @var array<string, string> */
	protected array $data;

	/** @param array<string, string> $data */
	public function __construct(string $name, array $data) {
		$this->name = $name;
		$this->data = $data;
	}

	public function get(string $name):?string {
		return $this->data[$name] ?? null;
	}

	public function contains(string $name):bool {
		return array_key_exists($name, $this->data);
	}

	public function with(string $key, string $value):static {
		$clone = clone $this;
		$clone->data[$key] = $value;
		return $clone;
	}

	/**
	 * @return Traversable<string, string>
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 */
	public function getIterator():Traversable {
		return new ArrayIterator($this->data);
	}

	/**
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 */
	public function offsetExists($offset):bool {
		return isset($this->data[$offset]);
	}

	/**
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 */
	public function offsetGet($offset):?string {
		return $this->get($offset);
	}

	/**
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 */
	public function offsetSet($offset, $value):void {
		throw new BadMethodCallException(
			"Immutable object can not be mutated: "
			. (string)$offset
			. "="
			. (string)$value
		);
	}

	/**
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 */
	public function offsetUnset($offset):void {
		throw new BadMethodCallException(
			"Immutable object can not be mutated: "
			. (string)$offset
		);
	}

	public function getName():string {
		return $this->name;
	}

	public function asArray():array {
		return $this->data;
	}
}
