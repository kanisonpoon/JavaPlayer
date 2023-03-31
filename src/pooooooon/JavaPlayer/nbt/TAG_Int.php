<?php
/** Minecraft NBT TAG_Int class.
*
* @version $Id: TAG_Int.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

final class TAG_Int
extends TAG_Value
{
// TAG_Value

  public static function store($value)
  {
    if($value < -2147483648 || $value > 2147483647)
      throw new \RangeException('Value is outside allowed range for a given type.');

    return pack('N', (int)$value);
  }

// NbtTag

  public static function readFrom(BinaryStream $str)
  {
    return new static(null, Dictionary::unpack('l', $str->get(4)));
  }
}
