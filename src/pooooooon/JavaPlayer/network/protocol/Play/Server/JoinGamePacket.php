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

use pooooooon\javaplayer\nbt\JByteTag;
use pooooooon\javaplayer\nbt\JCompoundTag;
use pooooooon\javaplayer\nbt\JFloatTag;
use pooooooon\javaplayer\nbt\JIntTag;
use pooooooon\javaplayer\nbt\JListTag;
use pooooooon\javaplayer\nbt\JStringTag;
use pooooooon\javaplayer\network\OutboundPacket;
use pooooooon\javaplayer\utils\JavaBinarystream;

class JoinGamePacket extends OutboundPacket
{

	/** @var int */
	public $entityId;
	/** @var bool */
	public $isHardcore = false;
	/** @var int */
	public $gamemode;
	/** @var int */
	public $previousGamemode;
	/** @var string[] */
	public $worldNames;
	/** @var string */
	public $dimensionCodec;
	/** @var string */
	public $dimension;
	/** @var string */
	public $worldName;
	/** @var int */
	public $hashedSeed;
	/** @var int */
	public $maxPlayers;
	/** @var int */
	public $viewDistance;
	/** @var int */
	public $simulationDistance;
	/** @var bool */
	public $reducedDebugInfo = false;
	/** @var bool */
	public $enableRespawnScreen = false;
	/** @var bool */
	public $isDebug = false;
	/** @var bool */
	public $isFlat = false;

	public function pid(): int
	{
		return self::JOIN_GAME_PACKET;
	}

	protected function encode(): void
	{
		$this->putInt($this->entityId);
		$this->putBool($this->isHardcore);
		$this->putByte($this->gamemode);
		$this->putByte($this->previousGamemode);
		$this->putVarInt(count($this->worldNames));
		foreach($this->worldNames as $worldName){
			$this->putString($worldName);
		}
		$this->put($this->dimensionCodec);
		$this->put($this->dimension);
		$this->putString($this->worldName);
		$this->putLong($this->hashedSeed);
		$this->putVarInt($this->maxPlayers);
		$this->putVarInt($this->viewDistance);
		$this->putBool($this->reducedDebugInfo);
		$this->putBool($this->enableRespawnScreen);
		$this->putBool($this->isDebug);
		$this->putBool($this->isFlat);
	}
}
