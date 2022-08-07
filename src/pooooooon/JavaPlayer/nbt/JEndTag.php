<?php
namespace pooooooon\javaplayer\nbt;
use Phpcraft\Connection;
use pooooooon\javaplayer\utils\JavaBinarystream;

class JEndTag extends JNBT
{
	const ORD = 0;

	function write(string $con, bool $inList = false): string
	{
		trigger_error("I'm begrudgingly allowing your call to NbtEnd::write but please note that NbtEnd is not a real tag and should not be treated as such.");
		$con .= JavaBinarystream::writeByte(0);
		return $con;
	}

	/**
	 * @return EndTag
	 */
	function copy(): JEndTag
	{
		return new JEndTag();
	}

	function __toString()
	{
		trigger_error("I'm begrudgingly allowing your call to NbtEnd::__toString but please note that NbtEnd is not a real tag and should not be treated as such.");
		return "{End}";
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
		trigger_error("I'm begrudgingly allowing your call to NbtEnd::toSNBT but please note that NbtEnd is not a real tag and should not be treated as such.");
		return "";
	}
}
