<?php
/** Minecraft NBT TAG_Float class.
*
* @version $Id: TAG_Float.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

if(\strlen(\pack('G', 1.2)) <> 4)
  \trigger_error('Float type byte size needs to be 4. Call ambulance.', E_USER_ERROR);

use pocketmine\utils\BinaryStream;

final class TAG_Float
extends TAG_Value
{
// TAG_Value

  public static function store($value)
  {
    return pack('G', $value);
  }

// NbtTag

  public static function readFrom(BinaryStream $str)
  {
    return new static(null, unpack('G', $str->get(4))[1]);
  }
}
