<?php
namespace pooooooon\javaplayer\nbt;
use GMP;
use Phpcraft\Connection;
use pooooooon\javaplayer\utils\JavaBinarystream;

class JIntArrayTag extends JAbstractListTag
{
	const ORD = 11;

	/**
	 * @param string $name The name of this tag.
	 * @param array<GMP> $children The integers in the array.
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
			$con .= JavaBinarystream::writeInt($child);
		}
		return $con;
	}

	function copy(): JIntArrayTag
	{
		return new JIntArrayTag($this->name, $this->children);
	}

	function __toString()
	{
		$str = "{IntArray \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".$child;
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
		return self::gmpListToSNBT($fancy, $inList, "I");
	}
}
