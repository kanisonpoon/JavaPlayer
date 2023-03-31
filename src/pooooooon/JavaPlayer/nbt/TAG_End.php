<?php
/** Minecraft NBT TAG_End class.
*
* @version $Id: TAG_End.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

final class TAG_End
implements NbtTag
{
  public function __toString():string
  {
    return '';
  }

// NbtTag

  public static function readFrom(BinaryStream $str)
  {
    throw new \BadMethodCallException('You may not retrieve something that exists only as a concept. Not really.');
  }

  public static function createFrom(BinaryStream $str)
  {
    return new static();
  }

  public function nbtSerialize()
  {
    return Dictionary::mapName(get_called_class());
  }
}
