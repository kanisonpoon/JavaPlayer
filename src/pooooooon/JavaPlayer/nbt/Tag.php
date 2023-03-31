<?php
/** Minecraft NBT Tag base class.
*
* @version $Id: Tag.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

abstract class Tag
implements NbtTag, \JsonSerializable, \Serializable
{
  public $name = null;

  abstract public function __debugInfo();

  public function __construct($name = null)
  {
    $this->name = isset($name) ? (string)$name : null;
  }

// NbtTag

  abstract public static function readFrom(BinaryStream $str);

  public static function createFrom(BinaryStream $str)
  {
    $_type = Dictionary::mapType($str->get(1));
    return $_type::createFrom($str);
  }

  public function nbtSerialize()
  {
    return isset($this->name) ? Dictionary::mapName(get_called_class()) . TAG_String::store($this->name) : '';
  }

// JsonSerializable

  abstract public function jsonSerialize();

// Serializable

  abstract public function serialize();
  abstract public function unserialize($blob);
}
