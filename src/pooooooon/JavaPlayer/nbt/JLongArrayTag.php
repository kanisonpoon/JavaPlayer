<?php
namespace pooooooon\javaplayer\nbt;
use GMP;
use Phpcraft\Connection;
use pooooooon\javaplayer\utils\JavaBinarystream;

class JLongArrayTag extends JAbstractListTag
{
	const ORD = 12;

	/**
	 * @param string $name The name of this tag.
	 * @param array<GMP> $children The longs in the array.
	 */
	function __construct(string $name, array $children = [])
	{
		$this->name = $name;
		$this->children = $children;
	}

	function write(string $con, bool $inList = false): string
	{
		if(!$inList)
		{
			$this->_write($con);
		}
		$con .= JavaBinarystream::writeInt(count($this->children));
		foreach($this->children as $child)
		{
			$con .= JavaBinarystream::writeLong($child);
		}
		return $con;
	}

	function __toString()
	{
		$str = "{LongArray \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".$child;
		}
		return $str."}";
	}

	function copy(): JLongArrayTag
	{
		return new JLongArrayTag($this->name, $this->children);
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
		return self::gmpListToSNBT($fancy, $inList, "L");
	}
}
