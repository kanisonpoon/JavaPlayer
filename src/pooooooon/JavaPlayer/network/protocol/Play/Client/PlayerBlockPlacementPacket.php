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

namespace pooooooon\javaplayer\network\protocol\Play\Client;

use pooooooon\javaplayer\network\InboundPacket;

class PlayerBlockPlacementPacket extends InboundPacket
{

	/** @var int */
	public $hand;
	/** @var int */
	public $x;
	/** @var int */
	public $y;
	/** @var int */
	public $z;
	/** @var int */
	public $face;
	/** @var float */
	public $cursorPositionX;
	/** @var float */
	public $cursorPositionY;
	/** @var float */
	public $cursorPositionZ;
	/** @var bool */
	public $isInsideBlock = false;

	public function pid(): int
	{
		return self::PLAYER_BLOCK_PLACEMENT_PACKET;
	}

	protected function decode(): void
	{
		$this->hand = $this->getVarInt();
		$this->getPosition($this->x, $this->y, $this->z);
		$this->face = $this->getVarInt();
		$this->cursorPositionX = $this->getFloat();
		$this->cursorPositionY = $this->getFloat();
		$this->cursorPositionZ = $this->getFloat();
		$this->isInsideBlock = $this->getBool();
	}

}
