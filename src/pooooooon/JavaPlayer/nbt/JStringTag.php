<?php
namespace pooooooon\javaplayer\nbt;
use Phpcraft\Connection;
use pooooooon\javaplayer\utils\JavaBinarystream;

class JStringTag extends JNBT
{
	const ORD = 8;
	/**
	 * The value of this tag.
	 *
	 * @var string $value
	 */
	public $value;

	/**
	 * @param string $name The name of this tag.
	 * @param string $value The value of this tag.
	 */
	function __construct(string $name, string $value = "")
	{
		$this->name = $name;
		$this->value = $value;
	}

	function write(string $con, bool $inList = false): string
	{
		if(!$inList)
		{
			$this->_write($con);
		}
		$con .= JavaBinarystream::writeShort(strlen($this->value));
		$con .= $this->value;
		return $con;
	}

	function copy(): JStringTag
	{
		return new JStringTag($this->name, $this->value);
	}

	function __toString()
	{
		return "{String \"".$this->name."\": ".$this->value."}";
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
		return ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":")).self::stringToSNBT($this->value);
	}
}
