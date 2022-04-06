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

class SetPlayerTeamPacket extends OutboundPacket
{

	const ACTION_CREATE = 0;
	const ACTION_REMOVE = 1;
	const ACTION_UPDATE = 2;
	const ACTION_ADD_PLAYER = 3;
	const ACTION_REMOVE_PLAYER = 4;

	/** @var string */
	public $teamName;
	/** @var int */
	public $action;
	/** @var string */
	public $displayName;
	/** @var string */
	public $prefix;
	/** @var string */
	public $suffix;
	/** @var bool */
	public $friendlyFire;
	/** @var bool */
	public $seeFriendlyInvisibles;
	/** @var string */
	public $nameTagVisibility;
	/** @var string */
	public $collisionRule;
	/** @var int */
	public $color;
	/** @var string[] */
	public $players;

	public function pid(): int
	{
		return self::TEAMS_PACKET;
	}

	protected function encode(): void
	{
		$this->putString($this->teamName);
		$this->putByte($this->action);
		if ($this->action == self::ACTION_CREATE || $this->action == self::ACTION_UPDATE) {
			$this->putString($this->displayName);
			$this->putByte(($this->friendlyFire ? 0x1 : 0x0) | ($this->seeFriendlyInvisibles ? 0x2 : 0x0));
			$this->putString($this->nameTagVisibility);
			$this->putString($this->collisionRule);
			$this->putVarInt($this->color);
			$this->putString($this->prefix);
			$this->putString($this->suffix);
		}

		if ($this->action == self::ACTION_CREATE || $this->action == self::ACTION_ADD_PLAYER || $this->action == self::ACTION_REMOVE_PLAYER) {
			$this->putVarInt(count($this->players));
			foreach($this->players as $player) {
				if ($player !== null) {
				    $this->putString($player);
				}
			}
		}
	}
}
