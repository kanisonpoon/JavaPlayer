<?php
namespace pooooooon\javaplayer\nbt;
use Phpcraft\Connection;
use pooooooon\javaplayer\utils\JavaBinarystream;

class JShortTag extends JNBT
{
	const ORD = 2;
	/**
	 * The value of this tag.
	 *
	 * @var int $value
	 */
	public $value;

	/**
	 * @param string $name The name of this tag.
	 * @param int $value The value of this tag.
	 */
	function __construct(string $name, int $value = 0)
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
		$con .= JavaBinarystream::writeShort($this->value);
		return $con;
	}

	function __toString()
	{
		return "{Short \"".$this->name."\": ".$this->value."}";
	}

	/**
	 * @return JShortTag
	 */
	function copy(): JShortTag
	{
		return new JShortTag($this->name, $this->value);
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
		return ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":")).$this->value."s";
	}
}
