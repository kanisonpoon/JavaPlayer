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
		foreach ($this->worldNames as $worldName) {
			$this->putString($worldName);
		}
		$this->put(base64_decode("CgAACgAYbWluZWNyYWZ0OmRpbWVuc2lvbl90eXBlCAAEdHlwZQAYbWluZWNyYWZ0OmRpbWVuc2lvbl90eXBlCQAFdmFsdWUKAAAAAQgABG5hbWUAE21pbmVjcmFmdDpvdmVyd29ybGQDAAJpZAAAAAAKAAdlbGVtZW50CAAEbmFtZQATbWluZWNyYWZ0Om92ZXJ3b3JsZAEAC3BpZ2xpbl9zYWZlAAEAB25hdHVyYWwBBQANYW1iaWVudF9saWdodAAAAAAIAAppbmZpbmlidXJuAB5taW5lY3JhZnQ6aW5maW5pYnVybl9vdmVyd29ybGQBABRyZXNwYXduX2FuY2hvcl93b3JrcwEBAAxoYXNfc2t5bGlnaHQBAQAJYmVkX3dvcmtzAQgAB2VmZmVjdHMAE21pbmVjcmFmdDpvdmVyd29ybGQBAAloYXNfcmFpZHMBAwAObG9naWNhbF9oZWlnaHQAAAEABQAQY29vcmRpbmF0ZV9zY2FsZT+AAAABAAl1bHRyYXdhcm0AAQALaGFzX2NlaWxpbmcAAwAFbWluX3kAAAAAAwAGaGVpZ2h0AAABAAAAAAoAGG1pbmVjcmFmdDp3b3JsZGdlbi9iaW9tZQgABHR5cGUAGG1pbmVjcmFmdDp3b3JsZGdlbi9iaW9tZQkABXZhbHVlCgAAAAEIAARuYW1lABBtaW5lY3JhZnQ6cGxhaW5zAwACaWQAAAAACgAHZWxlbWVudAgABG5hbWUAEG1pbmVjcmFmdDpwbGFpbnMIAA1wcmVjaXBpdGF0aW9uAARyYWluBQAFZGVwdGg+AAAABQALdGVtcGVyYXR1cmU/TMzNBQAFc2NhbGU9TMzNBQAIZG93bmZhbGw+zMzNCAAIY2F0ZWdvcnkABnBsYWlucwoAB2VmZmVjdHMEAAlza3lfY29sb3IAAAAAAHin/wQAD3dhdGVyX2ZvZ19jb2xvcgAAAAAABQUzBAAJZm9nX2NvbG9yAAAAAADA2P8EAAt3YXRlcl9jb2xvcgAAAAAAP3bkCgAKbW9vZF9zb3VuZAMACnRpY2tfZGVsYXkAABdwBQAGb2Zmc2V0QAAAAAgABXNvdW5kABZtaW5lY3JhZnQ6YW1iaWVudC5jYXZlAwATYmxvY2tfc2VhcmNoX2V4dGVudAAAAAgAAAAAAAA="));
		$this->put(base64_decode("CgAACAAEbmFtZQATbWluZWNyYWZ0Om92ZXJ3b3JsZAEAC3BpZ2xpbl9zYWZlAAEAB25hdHVyYWwBBQANYW1iaWVudF9saWdodAAAAAAIAAppbmZpbmlidXJuAB5taW5lY3JhZnQ6aW5maW5pYnVybl9vdmVyd29ybGQBABRyZXNwYXduX2FuY2hvcl93b3JrcwEBAAxoYXNfc2t5bGlnaHQBAQAJYmVkX3dvcmtzAQgAB2VmZmVjdHMAE21pbmVjcmFmdDpvdmVyd29ybGQBAAloYXNfcmFpZHMBAwAObG9naWNhbF9oZWlnaHQAAAEABQAQY29vcmRpbmF0ZV9zY2FsZT+AAAABAAl1bHRyYXdhcm0AAQALaGFzX2NlaWxpbmcAAwAFbWluX3kAAAAAAwAGaGVpZ2h0AAABAAA="));
		$this->putString($this->worldName);
		$this->putLong($this->hashedSeed);
		$this->putVarInt($this->maxPlayers);
		$this->putVarInt($this->viewDistance);
		$this->putVarInt($this->simulationDistance);
		$this->putBool($this->reducedDebugInfo);
		$this->putBool($this->enableRespawnScreen);
		$this->putBool($this->isDebug);
		$this->putBool($this->isFlat);
	}
}
