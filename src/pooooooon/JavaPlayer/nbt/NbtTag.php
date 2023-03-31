<?php

namespace pooooooon\javaplayer\nbt;

use pocketmine\utils\BinaryStream;

interface NbtTag
{
  // Read the tag data from a given source past the tag type/name.
  // The function returns "value tag" - a tag with null name.
  public static function readFrom(BinaryStream $str);
  // Create the tag from a given source, starting from the name.
  public static function createFrom(BinaryStream $str);
  // Creates an NBT presentation of the tag.
  public function nbtSerialize();
}
