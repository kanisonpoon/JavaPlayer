<?php
namespace pooooooon\javaplayer\nbt;
use ArrayAccess;
use Countable;
use Iterator;
abstract class JAbstractListTag extends JNBT implements Iterator, Countable, ArrayAccess
{
	/**
	 * The children of the list.
	 *
	 * @var array $children
	 */
	public $children;
	private $current = 0;

	function current()
	{
		return $this->children[$this->current];
	}

	function next():void
	{
		$this->current++;
	}

	function key()
	{
		return $this->current;
	}

	function valid():bool
	{
		return $this->current < count($this->children);
	}

	function rewind():void
	{
		$this->current = 0;
	}

	function offsetExists($offset):bool
	{
		return array_key_exists($offset, $this->children);
	}

	function offsetGet($offset)
	{
		return @$this->children[$offset];
	}

	function offsetSet($offset, $value):void
	{
		if($offset === null)
		{
			array_push($this->children, $value);
		}
		else
		{
			$this->children[$offset] = $value;
		}
	}

	function offsetUnset($offset):void
	{
		unset($this->children[$offset]);
	}

	function count():int
	{
		return count($this->children);
	}

	/**
	 * @param bool $fancy
	 * @param bool $inList
	 * @param string $type_char
	 * @return string
	 */
	protected function gmpListToSNBT(bool $fancy, bool $inList, string $type_char): string
	{
		$snbt = ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":"))."[".$type_char.";".($fancy ? " " : "");
		$c = count($this->children);
		for($i = 0; $i < $c; $i++)
		{
			$snbt .= gmp_strval($this->children[$i]).($i == $c - 1 ? "" : ($fancy ? ", " : ","));
		}
		return $snbt."]";
	}
}
