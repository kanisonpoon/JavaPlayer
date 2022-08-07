<?php
namespace pooooooon\javaplayer\nbt;
use ArrayAccess;
use Countable;
use Iterator;
use Phpcraft\Connection;
use pooooooon\javaplayer\utils\JavaBinarystream;
use SplObjectStorage;
class JCompoundTag extends JNBT implements Iterator, Countable, ArrayAccess
{
	const ORD = 10;
	/**
	 * The child tags of the compound.
	 *
	 * @var SplObjectStorage $children
	 */
	public $children;

	/**
	 * @param string $name The name of this tag.
	 * @param array<NBT> $children The child tags of the compound.
	 */
	function __construct(string $name, array $children = [])
	{
		$this->name = $name;
		$this->children = new SplObjectStorage();
		foreach($children as $child)
		{
			$this->children->attach($child);
		}
	}

	/**
	 * Returns true if the compound has a child with the given name.
	 *
	 * @param string $name
	 * @return boolean
	 */
	function hasChild(string $name): bool
	{
		foreach($this->children as $child)
		{
			if($child->name == $name)
			{
				return true;
			}
		}
		return false;
	}

	function write(string $con, bool $inList = false): string
	{
		if(!$inList)
		{
			$this->_write($con);
		}
		foreach($this->children as $child) {
			$child->write($con);
		}
		$con .= JavaBinarystream::writeByte(0);
		return $con;
	}

	function copy(): JCompoundTag
	{
		$tag = new JCompoundTag($this->name);
		$tag->children->addAll($this->children);
		return $tag;
	}

	function __toString()
	{
		$str = "{Compound \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".$child->__toString();
		}
		return $str."}";
	}

	/**
	 * Returns the NBT tag in SNBT (stringified NBT) format, as used in commands.
	 *
	 * @param bool $fancy
	 * @param boolean $inList Ignore this parameter.
	 * @return string
	 */
	function toSNBT(bool $fancy = false, bool $inList = false): string
	{
		$snbt = ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":"))."{".($fancy ? "\n" : "");
		$c = count($this->children) - 1;
		if($fancy)
		{
			$i = 0;
			foreach($this->children as $child)
			{
				$snbt .= self::indentString($child->toSNBT(true)).($i++ == $c ? "" : ",")."\n";
			}
		}
		else
		{
			$i = 0;
			foreach($this->children as $child)
			{
				$snbt .= $child->toSNBT().($i++ == $c ? "" : ",");
			}
		}
		return $snbt."}";
	}

	function current()
	{
		return $this->children->current();
	}

	function next():void
	{
		$this->children->next();
	}

	function key()
	{
		return $this->children->current()->name;
	}

	function valid():bool
	{
		return $this->children->valid();
	}

	function rewind():void
	{
		$this->children->rewind();
	}

	function offsetExists($offset):bool
	{
		return $this->offsetGet($offset) !== null;
	}

	function offsetGet($offset)
	{
		return $this->getChild($offset);
	}

	/**
	 * Gets a child of the compound by its name or null if not found.
	 *
	 * @param string $name
	 * @return JNBT
	 */
	function getChild(string $name)
	{
		foreach($this->children as $child)
		{
			assert($child instanceof JNBT);
			if($child->name == $name)
			{
				return $child;
			}
		}
		return null;
	}

	function offsetSet($offset, $value):void
	{
		assert($value instanceof JNBT);
		assert($offset === null || $offset === $value->name);
		$this->addChild($value);
	}

	/**
	 * Adds a child to the compound or replaces an existing one by the same name.
	 *
	 * @param JNBT $tag
	 * @return JCompoundTag $this
	 */
	function addChild(JNBT $tag)
	{
		if($tag instanceof JEndTag)
		{
			trigger_error("I'm not adding NbtEnd as the child of an NbtCompound because it is not a real tag and should not be treated as such.");
		}
		else
		{
			foreach($this->children as $child)
			{
				if($child->name == $tag->name)
				{
					$this->children->detach($child);
					break;
				}
			}
			$this->children->attach($tag);
		}
		return $this;
	}

	function offsetUnset($offset):void
	{
		$this->children->detach($this->getChild($offset));
	}

	function count():int
	{
		return $this->children->count();
	}
}
