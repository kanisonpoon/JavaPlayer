<?php
/** Minecraft NBT TAG_String class.
*
* @version $Id: TAG_String.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

final class TAG_String
extends TAG_Value
{
// TAG_Value

  public static function store($value)
  {
    $len = strlen($value);
    if($len < 0 || $len > 32767){
      throw new \UnexpectedValueException('Valid string length range is 0..32767.');
    }
    return TAG_Short::store($len) . $value;
  }

// NbtTag

  public static function readFrom(BinaryStream $str)
  {
    return new static(null, (string)$str->get(TAG_Short::readFrom($str)->value));
  }
}
