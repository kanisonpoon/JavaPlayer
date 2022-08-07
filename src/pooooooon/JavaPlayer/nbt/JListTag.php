<?php
namespace pooooooon\javaplayer\nbt;
use Phpcraft\Connection;
use pooooooon\javaplayer\utils\JavaBinarystream;

class JListTag extends JAbstractListTag
{
	const ORD = 9;
	/**
	 * The NBT tag type ID of children.
	 *
	 * @var int $childType
	 * @see NBT::ORD
	 */
	public $childType;

	/**
	 * @param string $name The name of this tag.
	 * @param int $childType The NBT Tag Type of children.
	 * @param array<NBT> $children The child tags of the list.
	 */
	function __construct(string $name, int $childType, array $children = [])
	{
		$this->name = $name;
		$this->childType = $childType;
		$this->children = $children;
	}

	function write(string $con, bool $inList = false): string
	{
		if(!$inList)
		{
			$this->_write($con);
		}
		$con .= JavaBinarystream::writeByte($this->childType);
		$con .= JavaBinarystream::writeInt(count($this->children));
		foreach($this as $child)
		{
			$child->write($con, true);
		}
		return $con;
	}

	/**
	 * @return JListTag
	 */
	function copy(): JListTag
	{
		return new JListTag($this->name, $this->childType, $this->children);
	}

	function __toString()
	{
		$str = "{List \"".$this->name."\":";
		foreach($this as $child)
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
		$snbt = ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":"))."[".($fancy ? "\n" : "");
		$c = count($this->children) - 1;
		if($fancy)
		{
			for($i = 0; $i <= $c; $i++)
			{
				$snbt .= self::indentString($this->children[$i]->toSNBT(true, true)).($i == $c ? "" : ",")."\n";
			}
		}
		else
		{
			for($i = 0; $i <= $c; $i++)
			{
				$snbt .= $this->children[$i]->toSNBT(false, true).($i == $c ? "" : ",");
			}
		}
		return $snbt."]";
	}
}
