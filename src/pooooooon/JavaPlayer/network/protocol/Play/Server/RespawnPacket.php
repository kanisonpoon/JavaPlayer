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

class RespawnPacket extends OutboundPacket
{

	/** @var string */
	public $dimension;
	/** @var string */
	public $worldName;
	/** @var int */
	public $hashedSeed;
	/** @var int */
	public $gamemode;
	/** @var int */
	public $previousGamemode;
	/** @var bool */
	public $isDebug = false;
	/** @var bool */
	public $isFlat = false;
	/** @var bool */
	public $copyMetadata = false;

	public function pid(): int
	{
		return self::RESPAWN_PACKET;
	}

	protected function encode(): void
	{
		$this->put($this->dimension);
		$this->putString($this->worldName);
		$this->putLong($this->hashedSeed);
		$this->putByte($this->gamemode);
		$this->putByte($this->previousGamemode);
		$this->putBool($this->isDebug);
		$this->putBool($this->isFlat);
		$this->putBool($this->copyMetadata);
	}

}
