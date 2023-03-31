<?php
/** Minecraft NBT TAG_Scalar_Array extension.
*
* @version $Id: TAG_Scalar_Array.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

abstract class TAG_Scalar_Array
extends TAG_Array
{
// TAG_Array

  protected function validate($value)
  {
    if(is_subclass_of($value, __NAMESPACE__ . '\\TAG_Value')){
      $value = $value->value;
    }
    if(!is_scalar($value)){
      throw new \InvalidArgumentException("Ivalid scalar value. No null's allowed, too.");
    }
    return $value;
  }
}
