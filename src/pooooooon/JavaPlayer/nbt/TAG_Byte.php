<?php
/** Minecraft NBT TAG_Byte class.
*
* @version $Id: TAG_Byte.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

final class TAG_Byte
extends TAG_Value
{
// TAG_Value

  public static function store($value)
  {
    if($value < -128 || $value > 127)
      throw new \RangeException('Value is outside allowed range for a given type.');

    return pack('c', (int)$value);
  }

// NbtTag

  public static function readFrom(BinaryStream $str)
  {
    return new static(null, unpack('c', $str->get(1))[1]);
  }
}
