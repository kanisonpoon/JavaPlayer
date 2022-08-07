<?php
namespace pooooooon\javaplayer\nbt;
use Phpcraft\Connection;
use pooooooon\javaplayer\utils\JavaBinarystream;

class JFloatTag extends JNBT
{
	const ORD = 5;
	/**
	 * The value of this tag.
	 *
	 * @var float $value
	 */
	public $value;

	/**
	 * @param string $name The name of this tag.
	 * @param float $value The value of this tag.
	 */
	function __construct(string $name, float $value = 0)
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
		$con .= JavaBinarystream::writeFloat($this->value);
		return $con;
	}

	function __toString()
	{
		return "{Float \"".$this->name."\": ".$this->value."}";
	}

	/**
	 * @return JFloatTag
	 */
	function copy(): JFloatTag
	{
		return new JFloatTag($this->name, $this->value);
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
		return ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":")).$this->value."f";
	}
}
