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

namespace pooooooon\javaplayer\network;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pooooooon\javaplayer\utils\JavaBinarystream;
use stdClass;

abstract class Packet extends stdClass
{

	/** @var string */
	protected $buffer;
	/** @var int */
	protected $offset = 0;

	public function write(): string
	{
		$this->buffer = "";
		$this->offset = 0;
		$this->encode();
		return JavaBinarystream::writeJavaVarInt($this->pid()) . $this->buffer;
	}

	protected abstract function encode(): void;

	public abstract function pid(): int;

	public function read(string $buffer, int $offset = 0): void
	{
		$this->buffer = $buffer;
		$this->offset = $offset;
		$this->decode();
	}

	protected abstract function decode(): void;

	public function putUUID(string $uuid)
	{
		$this->put(strrev(substr($uuid, 0, 8)));
		$this->put(strrev(substr($uuid, 8, 8)));
	}

	protected function getInt(): int
	{
		return JavaBinarystream::readInt($this->get(4));
	}

	protected function get($len): string
	{
		if ($len < 0) {
			$this->offset = strlen($this->buffer) - 1;

			return "";
		} elseif ($len === true) {
			return substr($this->buffer, $this->offset);
		}

		$buffer = "";
		for (; $len > 0; --$len, ++$this->offset) {
			$buffer .= @$this->buffer[$this->offset];
		}

		return $buffer;
	}

	protected function getPosition(int &$x = null, int &$y = null, int &$z = null): void
	{
		$long = $this->getLong();
		$x = $long >> 38;
		$y = $long & 0xFFF;
		$z = ($long << 26 >> 38);
	}

	protected function getLong(): int
	{
		return JavaBinarystream::readLong($this->get(8));
	}

	protected function getFloat(): float
	{
		return JavaBinarystream::readFloat($this->get(4));
	}

	protected function getDouble(): float
	{
		return JavaBinarystream::readDouble($this->get(8));
	}

	/**
	 * @return Item
	 */
	protected function getSlot(): Item
	{
		$hasItem = $this->getBool();
		if ($hasItem === false) { //Empty
			return VanillaItems::AIR();
		} else {

			$id = $this->getVarInt();
			$count = $this->getByte();//count or damage
			$nbt = $this->get(true);//getNbt

			//var_dump($id.",".$count);

			// $item = new ComputerItem($id);
			// if($item instanceof Durable){
			// 	$item->setDamage($count);
			// }else{
			// 	$item->setCount($count);
			// }
			$item = ItemFactory::getInstance()->get(0, 0, 0);
			//$itemNBT = ConvertUtils::convertNBTDataFromPCtoPE($nbt);
			//var_dump($itemNBT);
			//$item->setCompoundTag($itemNBT);

			//ConvertUtils::convertItemData(false, $item);

			return $item;
		}
	}

	protected function getBool(): bool
	{
		return $this->get(1) !== "\x00";
	}

	protected function getVarInt(): int
	{
		return JavaBinarystream::readComputerVarInt($this->buffer, $this->offset);
	}

	protected function getByte(): int
	{
		return ord($this->buffer[$this->offset++]);
	}

	protected function putSlot($item): void
	{
		if ($item instanceof ItemStackWrapper) {
			$item = $item->getItemStack();
		}
		if ($item instanceof ItemStack) {
			//ConvertUtils::convertItemData(true, $item);

			$this->putBool($item !== null);
			if ($item !== null) {
				$this->putVarInt(0);//id
				$this->putByte($item->getCount());//count
				if ($item->getNbt() !== null) {
					$itemNBT = clone $item->getNbt();
					//$this->put(ConvertUtils::convertNBTDataFromPEtoPC($itemNBT));
				} else {
					$this->put("\x00");//TAG_End
				}
			}
		}

	}

	protected function putBool(bool $v): void
	{
		$this->buffer .= ($v ? "\x01" : "\x00");
	}

	protected function putVarInt(int $v): void
	{
		$this->buffer .= JavaBinarystream::writeJavaVarInt($v);
	}

	protected function putByte(int $v): void
	{
		$this->buffer .= chr($v);
	}

	protected function put(string $str): void
	{
		$this->buffer .= $str;
	}

	protected function getShort(): int
	{
		return JavaBinarystream::readShort($this->get(2));
	}

	protected function getSignedShort(): int
	{
		return JavaBinarystream::readSignedShort($this->get(2));
	}

	protected function getTriad(): int
	{
		return JavaBinarystream::readTriad($this->get(3));
	}

	protected function getLTriad(): int
	{
		return JavaBinarystream::readTriad(strrev($this->get(3)));
	}

	protected function getSignedByte(): int
	{
		return ord($this->buffer[$this->offset++]) << 56 >> 56;
	}

	protected function getAngle(): float
	{
		return $this->getByte() * 360 / 256;
	}

	protected function getString(): string
	{
		return $this->get($this->getVarInt());
	}

	protected function feof(): bool
	{
		return !isset($this->buffer[$this->offset]);
	}

	protected function putInt(int $v): void
	{
		$this->buffer .= JavaBinarystream::writeInt($v);
	}

	protected function putPosition(int $x, int $y, int $z): void
	{
		$long = (($x & 0x3FFFFFF) << 38) | (($z & 0x3FFFFFF) << 12) | ($y & 0xFFF);
		$this->putLong($long);
	}

	protected function putLong(int $v): void
	{
		$this->buffer .= JavaBinarystream::writeLong($v);
	}

	protected function putFloat(float $v): void
	{
		$this->buffer .= JavaBinarystream::writeFloat($v);
	}

	protected function putDouble(float $v): void
	{
		$this->buffer .= JavaBinarystream::writeDouble($v);
	}

	protected function putShort(int $v): void
	{
		$this->buffer .= JavaBinarystream::writeShort($v);
	}

	protected function putTriad(int $v): void
	{
		$this->buffer .= JavaBinarystream::writeTriad($v);
	}

	protected function putLTriad(int $v): void
	{
		$this->buffer .= strrev(JavaBinarystream::writeTriad($v));
	}

	/**
	 * @param float $v any number is valid, including negative numbers and numbers greater than 360
	 */
	protected function putAngle(float $v): void
	{
		$this->putByte((int)round($v * 256 / 360));
	}

	protected function putString(string $v): void
	{
		$this->putVarInt(strlen($v));
		$this->put($v);
	}
}