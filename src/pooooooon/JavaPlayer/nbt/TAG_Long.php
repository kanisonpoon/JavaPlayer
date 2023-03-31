<?php
/** Minecraft NBT TAG_Long class.
*
* @version $Id: TAG_Long.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

final class TAG_Long
extends TAG_Value
{
// TAG_Value

  public static function store($value)
  {
    return pack('J', (int)$value);
  }

// NbtTag

  public static function readFrom(BinaryStream $str)
  {
    return new static(null, Dictionary::unpack('q', $str->get(8)));
  }
}
