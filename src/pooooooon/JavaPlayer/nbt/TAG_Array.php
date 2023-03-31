<?php
/** Minecraft NBT TAG_Array base class.
*
* @version $Id: TAG_Array.php 282 2018-03-27 20:19:29Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

abstract class TAG_Array
extends Tag
implements \ArrayAccess, \Countable, \Iterator
{
  protected $content = array();
  protected $position = 0;

  abstract protected function validate($value);
  abstract protected function store();

  public function __construct($name = null, array $content = array())
  {
    parent::__construct($name);
    foreach($content as $value)
    {
      $this[] = $value;
    }
  }

// Tag

  public function __debugInfo()
  {
    return ['name' => $this->name, 'content' => $this->content];
  }

// NbtTag

  public static function createFrom(BinaryStream $str)
  {
    $self = new static(TAG_String::readFrom($str));
    return static::readFrom($str, $self);
  }

  public function nbtSerialize()
  {
    $result = parent::nbtSerialize();
    foreach($this->store() as $value)
      $result .= $value;

    return $result;
  }

// ArrayAccess

  public function offsetSet($offset, $value) :void
  {
    $value = $this->validate($value);
    if(is_null($offset))
      $this->content[] = $value;
    else
      $this->content[$offset] = $value;
  }

  public function offsetExists($offset):bool
  {
    return isset($this->content[$offset]);
  }

  public function offsetUnset($offset):void
  {
    unset($this->content[$offset]);
  }

  public function offsetGet($offset)
  {
    return isset($this->content[$offset]) ? $this->content[$offset] : null;
  }

// Countable

  public function count():int
  {
    return count($this->content);
  }

// Iterator

  public function current()
  {
    return $this->valid() ? current($this->content) : null;
  }

  public function key()
  {
    return key($this->content);
  }

  public function next()
  {
    if($this->valid())
    {
      $this->position++;
      return next($this->content);
    }
    return null;
  }

  public function rewind()
  {
    $this->position = 0;
    return reset($this->content);
  }

  public function valid():bool
  {
    return $this->position < count($this->content);
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
    return serialize(['name' => $this->name, 'content' => $this->content]);
  }

  public function unserialize($blob)
  {
    $data = unserialize($blob);
    $self = new static($data['name'], $data['content']);
    $this->name = $self->name;
    $this->content = $self->content;
  }
}
