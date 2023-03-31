<?php
/** Minecraft NBT TAG_Value base class.
*
* @version $Id: TAG_Value.php 282 2018-03-27 20:19:29Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

abstract class TAG_Value
extends Tag
{
  public $value = null;

  abstract public static function store($value);

  public function __construct($name = null, $value = null)
  {
    parent::__construct($name);
    $this->value = is_subclass_of($value, __CLASS__) ? $value->value : $value;
  }

  public function __toString()
  {
    return $this->value;
  }

// Tag

  public function __debugInfo()
  {
    return ['name' => $this->name, 'value' => $this->value];
  }

// NbtTag

  public static function createFrom(BinaryStream $str)
  {
    return new static(TAG_String::readFrom($str), static::readFrom($str));
  }

  public function nbtSerialize()
  {
    return parent::nbtSerialize() . static::store($this->value);
  }

// JsonSerializable

  public function jsonSerialize()
  {
    error_log(__METHOD__);
    //return (object)[];
  }

// Serializable

  public function serialize()
  {
    return serialize(['name' => $this->name, 'value' => $this->value]);
  }

  public function unserialize($blob)
  {
    $data = unserialize($blob);
    $self = new static($data['name'], $data['value']);
    $this->name = $self->name;
    $this->value = $self->value;
  }
}
