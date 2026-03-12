<?php
namespace Gt\Config;

use ArrayAccess;
use BadMethodCallException;
use Gt\TypeSafeGetter\NullableTypeSafeGetter;
use Iterator;

/**
 * @implements ArrayAccess<string, string>
 * @implements Iterator<string, string>
 */
class ConfigSection implements ArrayAccess, Iterator {
	use NullableTypeSafeGetter;

	protected string $name;
	/** @var array<string, string> */
	protected array $data;
	protected int $iteratorIndex;

	/** @param array<string, string> $data */
	public function __construct(string $name, array $data) {
		$this->name = $name;
		$this->data = $data;
		$this->iteratorIndex = 0;
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
	 * @link http://php.net/manual/en/iterator.current.php
	 */
	public function current():string {
		$key = $this->getIteratorKey();
		return $this->data[$key];
	}

	/**
	 * @link http://php.net/manual/en/iterator.next.php
	 */
	public function next():void {
		$this->iteratorIndex++;
	}

	/**
	 * @link http://php.net/manual/en/iterator.key.php
	 */
	public function key():?string {
		return $this->getIteratorKey();
	}

	/**
	 * @link http://php.net/manual/en/iterator.valid.php
	 */
	public function valid():bool {
		$key = $this->getIteratorKey();
		return isset($this->data[$key ?? ""]);
	}

	/**
	 * @link http://php.net/manual/en/iterator.rewind.php
	 */
	public function rewind():void {
		$this->iteratorIndex = 0;
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
		throw new BadMethodCallException("Immutable object can not be mutated");
	}

	/**
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 */
	public function offsetUnset($offset):void {
		throw new BadMethodCallException("Immutable object can not be mutated");
	}

	public function getName():string {
		return $this->name;
	}

	protected function getIteratorKey():?string {
		$keys = array_keys($this->data);
		return $keys[$this->iteratorIndex] ?? null;
	}
}
