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

class SoundEffectPacket extends OutboundPacket
{

	/** @var int */
	public $soundId;
	/** @var int */
	public $soundCategory;
	/** @var int */
	public $effectPositionX;
	/** @var int */
	public $effectPositionY;
	/** @var int */
	public $effectPositionZ;
	/** @var float */
	public $volume;
	/** @var float */
	public $pitch;

	public function pid(): int
	{
		return self::SOUND_EFFECT_PACKET;
	}

	protected function encode(): void
	{
		$this->putVarInt($this->soundId);
		$this->putVarInt($this->soundCategory);
		$this->putInt($this->effectPositionX * 8);
		$this->putInt($this->effectPositionY * 8);
		$this->putInt($this->effectPositionZ * 8);
		$this->putFloat($this->volume);
		$this->putFloat($this->pitch);
	}

}
