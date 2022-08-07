<?php
namespace pooooooon\javaplayer\nbt;
use GMP;
use Phpcraft\Connection;
use pooooooon\javaplayer\utils\JavaBinarystream;

class JIntTag extends JNBT
{
	const ORD = 3;
	/**
	 * The value of this tag.
	 *
	 * @var GMP $value
	 */
	public $value;

	/**
	 * @param string $name The name of this tag.
	 * @param GMP|string|integer $value The value of this tag.
	 */
	function __construct(string $name, $value = 0)
	{
		$this->name = $name;
		if(!$value instanceof GMP)
		{
			$value = gmp_init($value);
		}
		$this->value = $value;
	}

	function write(string $con, bool $inList = false): string
	{
		if(!$inList)
		{
			$this->_write($con);
		}
		$con .= JavaBinarystream::writeInt(gmp_intval($this->value));
		return $con;
	}

	/**
	 * @return JIntTag
	 */
	function copy(): JIntTag
	{
		return new JIntTag($this->name, $this->value);
	}

	function __toString()
	{
		return "{Int \"".$this->name."\": ".gmp_strval($this->value)."}";
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
		return ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":")).gmp_strval($this->value);
	}
}
