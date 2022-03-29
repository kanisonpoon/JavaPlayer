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

class PlayerInfoPacket extends OutboundPacket
{

	const TYPE_ADD = 0;
	const TYPE_GAMEMODE = 1;
	const TYPE_LATENCY = 2;
	const TYPE_UPDATE_NAME = 3;
	const TYPE_REMOVE = 4;

	/** @var int */
	public $actionId;
	/** @var array */
	public $players = [];

	public function pid(): int
	{
		return self::PLAYER_INFO_PACKET;
	}

	protected function encode(): void
	{
		$this->putVarInt($this->actionId);
		$this->putVarInt(count($this->players));
		foreach ($this->players as $player) {
			$this->put($player[0]);//UUID

			switch ($this->actionId) {
				case self::TYPE_ADD:
					$this->putString($player[1]); //PlayerName
					$this->putVarInt(count($player[2])); //Count Property

					foreach ($player[2] as $propertyData) {
						$this->putString($propertyData["name"]); //Name
						$this->putString($propertyData["value"]); //Value
						$this->putBool(isset($propertyData["signature"]));
						if (isset($propertyData["signature"])) {
							$this->putString($propertyData["signature"]); //Property
						}
					}

					$this->putVarInt($player[3]); //Gamemode
					$this->putVarInt($player[4]); //Ping
					$this->putBool($player[5]); //has Display name
					if ($player[5]) {
						$this->putString($player[6]); //Display name
					}
					break;
				case self::TYPE_UPDATE_NAME:
					$this->putBool($player[1]); //has Display name
					if ($player[1]) {
						$this->putString($player[2]);//Display name
					}
					break;
				case self::TYPE_REMOVE:
					break;
				default:
					echo "PlayerInfoPacket: " . $this->actionId . "\n";
					break;
			}
		}
	}

}
