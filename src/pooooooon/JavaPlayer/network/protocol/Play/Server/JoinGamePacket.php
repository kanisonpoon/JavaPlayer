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
		$this->put(base64_decode("CgAACgAYbWluZWNyYWZ0OmRpbWVuc2lvbl90eXBlCAAEdHlwZQAYbWluZWNyYWZ0OmRpbWVuc2lvbl90eXBlCQAFdmFsdWUKAAAAAQgABG5hbWUAE21pbmVjcmFmdDpvdmVyd29ybGQDAAJpZAAAAAAKAAdlbGVtZW50CAAEbmFtZQATbWluZWNyYWZ0Om92ZXJ3b3JsZAEAC3BpZ2xpbl9zYWZlAAEAB25hdHVyYWwBBQANYW1iaWVudF9saWdodAAAAAAIAAppbmZpbmlidXJuAB8jbWluZWNyYWZ0OmluZmluaWJ1cm5fb3ZlcndvcmxkAQAUcmVzcGF3bl9hbmNob3Jfd29ya3MBAQAMaGFzX3NreWxpZ2h0AQEACWJlZF93b3JrcwEIAAdlZmZlY3RzABNtaW5lY3JhZnQ6b3ZlcndvcmxkAQAJaGFzX3JhaWRzAQMADmxvZ2ljYWxfaGVpZ2h0AAABAAUAEGNvb3JkaW5hdGVfc2NhbGU/gAAAAQAJdWx0cmF3YXJtAAEAC2hhc19jZWlsaW5nAAMABW1pbl95AAAAAAMABmhlaWdodAAAAQAAAAAKABhtaW5lY3JhZnQ6d29ybGRnZW4vYmlvbWUIAAR0eXBlABhtaW5lY3JhZnQ6d29ybGRnZW4vYmlvbWUJAAV2YWx1ZQoAAAABCAAEbmFtZQAQbWluZWNyYWZ0OnBsYWlucwMAAmlkAAAAAAoAB2VsZW1lbnQIAARuYW1lABBtaW5lY3JhZnQ6cGxhaW5zCAANcHJlY2lwaXRhdGlvbgAEcmFpbgUABWRlcHRoPgAAAAUAC3RlbXBlcmF0dXJlP0zMzQUABXNjYWxlPUzMzQUACGRvd25mYWxsPszMzQgACGNhdGVnb3J5AAZwbGFpbnMKAAdlZmZlY3RzBAAJc2t5X2NvbG9yAAAAAAB4p/8EAA93YXRlcl9mb2dfY29sb3IAAAAAAAUFMwQACWZvZ19jb2xvcgAAAAAAwNj/BAALd2F0ZXJfY29sb3IAAAAAAD925AoACm1vb2Rfc291bmQDAAp0aWNrX2RlbGF5AAAXcAUABm9mZnNldEAAAAAIAAVzb3VuZAAWbWluZWNyYWZ0OmFtYmllbnQuY2F2ZQMAE2Jsb2NrX3NlYXJjaF9leHRlbnQAAAAIAAAAAAAA"));
		$this->put(base64_decode("CgAACAAEbmFtZQATbWluZWNyYWZ0Om92ZXJ3b3JsZAEAC3BpZ2xpbl9zYWZlAAEAB25hdHVyYWwBBQANYW1iaWVudF9saWdodAAAAAAIAAppbmZpbmlidXJuAB8jbWluZWNyYWZ0OmluZmluaWJ1cm5fb3ZlcndvcmxkAQAUcmVzcGF3bl9hbmNob3Jfd29ya3MBAQAMaGFzX3NreWxpZ2h0AQEACWJlZF93b3JrcwEIAAdlZmZlY3RzABNtaW5lY3JhZnQ6b3ZlcndvcmxkAQAJaGFzX3JhaWRzAQMADmxvZ2ljYWxfaGVpZ2h0AAABAAUAEGNvb3JkaW5hdGVfc2NhbGU/gAAAAQAJdWx0cmF3YXJtAAEAC2hhc19jZWlsaW5nAAMABW1pbl95AAAAAAMABmhlaWdodAAAAQAA"));
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
