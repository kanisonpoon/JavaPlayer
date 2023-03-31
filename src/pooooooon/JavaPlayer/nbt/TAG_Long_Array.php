<?php
/** Minecraft NBT TAG_Long_Array class.
*
* @version $Id: TAG_Long_Array.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

final class TAG_Long_Array
extends TAG_Scalar_Array
{
// TAG_Array

  protected function store()
  {
    yield TAG_Int::store(count($this->content));
    foreach($this->content as $value)
    {
      yield TAG_Long::store($value);
    }
  }

// NbtTag

  public static function readFrom(BinaryStream $str, TAG_Array $into = null)
  {
    $self = $into ?: new static();
    $size = TAG_Int::readFrom($str)->value;

    for($i = 0; $i < $size; $i++){
      $self[] = TAG_Long::readFrom($str);
    }
    return $self;
  }
}
