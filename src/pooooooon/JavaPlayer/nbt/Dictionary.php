<?php
/** NBT dictionary and conversion tools
*
*
*
* @version $Id: Dictionary.php 280 2018-03-27 16:05:51Z anrdaemon $
*/

namespace pooooooon\javaplayer\nbt;

define('_IS_BE', unpack('v', pack('S', 1))[1] > 1);

final class Dictionary
{
  private static $typeMap = array(
      "\x0" => __NAMESPACE__ . '\TAG_End',
      "\x1" => __NAMESPACE__ . '\TAG_Byte',
      "\x2" => __NAMESPACE__ . '\TAG_Short',
      "\x3" => __NAMESPACE__ . '\TAG_Int',
      "\x4" => __NAMESPACE__ . '\TAG_Long',
      "\x5" => __NAMESPACE__ . '\TAG_Float',
      "\x6" => __NAMESPACE__ . '\TAG_Double',
      "\x7" => __NAMESPACE__ . '\TAG_Byte_Array',
      "\x8" => __NAMESPACE__ . '\TAG_String',
      "\x9" => __NAMESPACE__ . '\TAG_List',
      "\xA" => __NAMESPACE__ . '\TAG_Compound',
      "\xB" => __NAMESPACE__ . '\TAG_Int_Array',
      "\xC" => __NAMESPACE__ . '\TAG_Long_Array',
  );
  private static $nameMap;

  private static function init()
  {
    self::$nameMap = array_flip(self::$typeMap);
  }

  public static function mapType($type)
  {
    if(!isset(self::$typeMap[$type]))
      throw new \OutOfBoundsException("Unknown tag type 0x" . bin2hex($type));

    return self::$typeMap[$type];
  }

  public static function mapName($name)
  {
    $tag = self::$nameMap[$name] ?? self::$nameMap[__NAMESPACE__ . "\\$name"] ?? null;
    if(!isset($tag))
      throw new \OutOfBoundsException("Unknown tag name '$name'");

    return $tag;
  }

  public static function convert($value)
  {
    return _IS_BE ? $value : strrev($value);
  }

// unpack() wrapper, because damned "machine byte order"
  public static function unpack($format, $value)
  {
    return unpack($format, static::convert($value))[1];
  }

  public function __construct()
  {
    if(!isset(self::$nameMap))
      self::init();
  }
}

return new Dictionary;
