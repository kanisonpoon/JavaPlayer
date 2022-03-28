<?php
/**
 *  ______  __         ______               __    __
 * |   __ \|__|.-----.|   __ \.----..-----.|  |_ |  |--..-----..----.
 * |   __ <|  ||  _  ||   __ <|   _||  _  ||   _||     ||  -__||   _|
 * |______/|__||___  ||______/|__|  |_____||____||__|__||_____||__|
 *             |_____|
 *
 * BigBrother plugin for PocketMine-MP
 * Copyright (C) 2014-2015 shoghicp <https://github.com/shoghicp/BigBrother>
 * Copyright (C) 2016- BigBrotherTeam
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author BigBrotherTeam
 * @link   https://github.com/BigBrotherTeam/BigBrother
 *
 */

declare(strict_types=1);

namespace pooooooon\javaplayer\utils;

use FG\Utility\BigIntegerBcmath;
use phpseclib\Math\BigInteger;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\utils\Binary;
use pooooooon\javaplayer\network\Session;
use Ramsey\Uuid\Converter\Number\GenericNumberConverter;
use const pocketmine\DEBUG;

class JavaBinarystream extends Binary
{

	/**
	 * @param string $input
	 * @return string
	 */
	public static function sha1(string $input): string
	{
		$number = BigIntegerBcmath::create(sha1($input, true), -256);
		$zero = BigIntegerBcmath::create(0);
		$num = GenericNumberConverter::toHex($number->__toString());
		return ($zero->compare($number) <= 0 ? "" : "-") . ltrim(($num), "0");
	}

	/**
	 * @param string $uuid
	 * @return string
	 */
	public static function UUIDtoString(string $uuid): string
	{
		return substr($uuid, 0, 8) . "-" . substr($uuid, 8, 4) . "-" . substr($uuid, 12, 4) . "-" . substr($uuid, 16, 4) . "-" . substr($uuid, 20);
	}

	public static function hexentities($str)
	{//debug
		$return = '';
		for ($i = 0, $iMax = strlen($str); $i < $iMax; $i++) {
			$return .= '&#x' . bin2hex(substr($str, $i, 1)) . ';';
		}
		return $return;
	}

	/**
	 * @param MetadataProperty[] $data
	 * @return string
	 */
	public static function writeMetadata(array $data): string
	{
		if (!isset($data["convert"])) {
			$data = ConvertUtils::convertPEToPCMetadata($data);
		}

		// if(false){
		// 	var_dump($data);
		// }

		$m = "";

		foreach ($data as $bottom => $d) {
			$d = $d->getTypeId();
			if ($bottom === "convert") {
				continue;
			}

			assert(is_int($bottom));
			$m .= self::writeByte($bottom);
			$m .= self::writeJavaVarInt($d[0]);

			switch ($d[0]) {
				case 0://Byte
					$m .= self::writeByte($d[1]);
					break;
				case 1://VarInt
					$m .= self::writeJavaVarInt($d[1]);
					break;
				case 2://Float
					$m .= self::writeFloat($d[1]);
					break;
				case 3://String
				case 4://component
					$m .= self::writeJavaVarInt(strlen($d[1])) . $d[1];
					break;
				case 5://Optcomponent
					$m .= self::writeBool(false);
					// $m .= self::writeBool($d[1][0]);
					// if($d[1][0]){
					// 	$m .= self::writeJavaVarInt(strlen($d[1][1])) . $d[1][1];
					// }

					break;
				case 6://Slot
					// @var Item $item
					$item = ItemFactory::getInstance()->get($d[1]);
					if ($item->getId() === 0) {
						$m .= self::writeBool(false);
					} else {
						$m .= self::writeShort($item->getId());
						$m .= self::writeByte($item->getCount());
						$m .= self::writeShort($item->getDamage());
						//$m .= self::writeShort($item->getDamage());

						if ($item->hasCompoundTag()) {
							$itemNBT = clone $item->getNamedTag();
							$m .= ConvertUtils::convertNBTDataFromPEtoPC($itemNBT);
						} else {
							$m .= "\x00";//TAG_End
						}
					}

					$m .= self::writeBool(false);
					break;
				case 7://Boolean
					$m .= self::writeByte($d[1] ? 1 : 0);
					break;
				case 8://Rotation
					$m .= self::writeFloat($d[1][0]);
					$m .= self::writeFloat($d[1][1]);
					$m .= self::writeFloat($d[1][2]);
					break;
				case 9://Position
					$long = (($d[1][0] & 0x3FFFFFF) << 38) | (($d[1][1] & 0xFFF) << 26) | ($d[1][2] & 0x3FFFFFF);
					$m .= self::writeLong($long);
					break;
				case 10://OptPos
					$m .= self::writeBool($d[1][0]);
					if ($d[1][0]) {
						$long = (($d[1][1][0] & 0x3FFFFFF) << 38) | (($d[1][1][1] & 0xFFF) << 26) | ($d[1][1][2] & 0x3FFFFFF);
						$m .= self::writeLong($long);
					}
					break;
			}
		}

		$m .= "\xff";

		return $m;
	}

	/**
	 * @param int $number
	 * @return string
	 */
	public static function writeJavaVarInt(int $number): string
	{
		$encoded = "";
		do {
			$next_byte = $number & 0x7f;
			$number >>= 7;

			if ($number > 0) {
				$next_byte |= 0x80;
			}

			$encoded .= chr($next_byte);
		} while ($number > 0);

		return $encoded;
	}

	/**
	 * @param string $buffer
	 * @param int    &$offset
	 * @phpstan-param int $offset
	 * @return int
	 */
	public static function readComputerVarInt(string $buffer, int &$offset = 0): int
	{
		$number = 0;
		$shift = 0;

		while (true) {
			$c = ord($buffer[$offset++]);
			$number |= ($c & 0x7f) << $shift;
			$shift += 7;
			if (($c & 0x80) === 0x00) {
				break;
			}
		}
		return $number;
	}

	/**
	 * @param Session $session
	 * @param int     &$offset
	 * @phpstan-param int $offset
	 * @return int|bool
	 */
	public static function readVarIntSession(Session $session, int &$offset = 0)
	{
		$number = 0;
		$shift = 0;

		while (true) {
			$b = $session->read(1);
			$c = ord($b);
			$number |= ($c & 0x7f) << $shift;
			$shift += 7;
			++$offset;
			if ($b === false) {
				return false;
			} elseif (($c & 0x80) === 0x00) {
				break;
			}
		}
		return $number;
	}

	/**
	 * @param resource $fp
	 * @param int      &$offset
	 * @phpstan-param int $offset
	 * @return int|bool
	 */
	public static function readVarIntStream($fp, int &$offset = 0)
	{
		$number = 0;
		$shift = 0;

		while (true) {
			$b = fgetc($fp);
			$c = ord($b);
			$number |= ($c & 0x7f) << $shift;
			$shift += 7;
			++$offset;
			if ($b === false) {
				return false;
			} elseif (($c & 0x80) === 0x00) {
				break;
			}
		}
		return $number;
	}
}
