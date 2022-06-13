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

namespace pooooooon\javaplayer\network\protocol\Play\Server;

use pocketmine\utils\UUID;
use pooooooon\javaplayer\network\OutboundPacket;

class SpawnEntityPacket extends OutboundPacket
{

	const BOAT = 1;
	const ITEM_STACK = 37;
	const AREA_EFFECT_CLOUD = 3;
	const MINECART = 10;
	const ACTIVATED_TNT = 50;
	const ENDER_CRYSTAL = 51;
	const TIPPED_ARROW = 60;
	const SNOWBALL = 61;
	const EGG = 62;
	const FIREBALL = 63;
	const FIRE_CHARGE = 64;
	const THROWN_ENDERPEARL = 65;
	const WITHER_SKULL = 66;
	const SHULKER_BULLET = 67;
	const LLAMA_SPIT = 68;
	const FALLING_OBJECTS = 70;
	const ITEM_FRAMES = 71;
	const EYE_OF_ENDER = 72;
	const THROWN_POTION = 73;
	const THROWN_EXP_BOTTLE = 75;
	const FIREWORK_ROCKET = 76;
	const LEASH_KNOT = 77;
	const ARMOR_STAND = 78;
	const EVOCATION_FANGS = 79;
	const FISHING_HOOK = 90;
	const SPECTRAL_ARROW = 91;
	const DRAGON_FIREBALL = 93;

	/** @var int */
	public $entityId;
	/** @var string */
	public $uuid;
	/** @var int */
	public $type;
	/** @var float */
	public $x;
	/** @var float */
	public $y;
	/** @var float */
	public $z;
	/** @var float */
	public $pitch;
	/** @var float */
	public $yaw;
	/** @var float */
	public $headyaw;
	/** @var float */
	public $headyaw = 0;
	/** @var int */
	public $data = 0;
	/** @var bool */
	public $sendVelocity = false;
	/** @var float */
	public $velocityX = 0;
	/** @var float */
	public $velocityY = 0;
	/** @var float */
	public $velocityZ = 0;

	public function pid(): int
	{
		return self::SPAWN_ENTITY_PACKET;
	}

	protected function encode(): void
	{
		$this->putVarInt($this->entityId);
		$this->put($this->uuid);//
		$this->putVarInt($this->type);
		$this->putDouble($this->x);
		$this->putDouble($this->y);
		$this->putDouble($this->z);
		$this->putAngle($this->pitch);
		$this->putAngle($this->yaw);
		$this->putAngle($this->headyaw);
		$this->putVarInt($this->data);
		$this->putShort((int)round($this->velocityX * 8000));
		$this->putShort((int)round($this->velocityY * 8000));
		$this->putShort((int)round($this->velocityZ * 8000));
	}

}
