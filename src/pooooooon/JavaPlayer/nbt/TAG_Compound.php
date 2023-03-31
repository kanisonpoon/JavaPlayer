<?php
/** Minecraft NBT TAG_Compound class.
*
* @version $Id: TAG_Compound.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

final class TAG_Compound
extends TAG_Array
{
// TAG_Array

  protected function validate($value)
  {
    if(!is_subclass_of($value, __NAMESPACE__ . '\\Tag'))
      throw new \InvalidArgumentException("Elements of the list must be NBT tags. (And not 'End' tags!)");

    if(!isset($value->name))
      throw new \InvalidArgumentException("Elements of the list must have a name. Even if it's an empty name.");

    return $value;
  }

  protected function store()
  {
    foreach($this->content as $value){
      yield $this->validate($value)->nbtSerialize();
    }
    yield Dictionary::mapName('TAG_End');
  }

// NbtTag

  public static function readFrom(BinaryStream $str, TAG_Array $into = null)
  {
    $self = $into ?: new static();
    while($tag = Tag::createFrom($str)){
      if($tag instanceof TAG_End){
        break;
      }else{
        $self[] = $tag;
      }
    }
    return $self;
  }
}
