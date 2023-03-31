<?php
/** Minecraft NBT TAG_Short class.
*
* @version $Id: TAG_Short.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

final class TAG_Short
extends TAG_Value
{
// TAG_Value

  public static function store($value)
  {
    if($value < -32768 || $value > 32767){
      throw new \RangeException('Value is outside allowed range for a given type.');
    }
    return pack('n', (int)$value);
  }

// NbtTag

  public static function readFrom(BinaryStream $str)
  {
    return new static(null, Dictionary::unpack('s', $str->get(2)));
  }
}
