<?php
namespace pooooooon\javaplayer\nbt;
use Phpcraft\Connection;
use pooooooon\javaplayer\utils\JavaBinarystream;

class JByteArrayTag extends JAbstractListTag
{
	const ORD = 7;

	/**
	 * @param string $name The name of this tag.
	 * @param array<int> $children The bytes in the array.
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
			$con .= pack("c", $child);
		}
		return $con;
	}

	/**
	 * @return JByteArrayTag
	 */
	function copy(): JByteArrayTag
	{
		return new JByteArrayTag($this->name, $this->children);
	}

	function __toString()
	{
		$str = "{ByteArray \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".dechex($child);
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
		$snbt = ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":"))."[B;".($fancy ? " " : "");
		$c = count($this->children);
		for($i = 0; $i < $c; $i++)
		{
			$snbt .= $this->children[$i].($i == $c - 1 ? "" : ($fancy ? ", " : ","));
		}
		return $snbt."]";
	}
}
