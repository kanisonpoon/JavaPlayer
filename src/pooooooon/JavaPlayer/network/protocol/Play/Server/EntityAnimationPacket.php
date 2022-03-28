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

use pooooooon\javaplayer\network\OutboundPacket;

class EntityAnimationPacket extends OutboundPacket
{

	const ANIMATION_SWING_MAIN_ARM = 0;
	const ANIMATION_TAKE_DAMAGE = 1;
	const ANIMATION_LEAVE_BED = 2;
	const ANIMATION_SWING_OFFHAND = 3;
	const ANIMATION_CRITICAL_EFFECT = 4;
	const ANIMATION_MAGIC_EFFECT = 5;

	/** @var int */
	public $entityId;
	/** @var int */
	public $animation;

	public function pid(): int
	{
		return self::ENTITY_ANIMATION_PACKET;
	}

	protected function encode(): void
	{
		$this->putVarInt($this->entityId);
		$this->putByte($this->animation);
	}

}
