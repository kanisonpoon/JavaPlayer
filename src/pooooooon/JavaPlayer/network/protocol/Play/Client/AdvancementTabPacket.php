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

class AdvancementTabPacket extends InboundPacket
{

	const TYPE_OPENED_TAB = 0;
	const TYPE_CLOSED_TAB = 1;

	/** @var int */
	public $status;
	/** @var string */
	public $tabId = "";

	public function pid(): int
	{
		return self::ADVANCEMENT_TAB_PACKET;
	}

	protected function decode(): void
	{
		$this->status = $this->getVarInt();
		if ($this->status === self::TYPE_OPENED_TAB) {
			$this->tabId = $this->getString();
		}
	}

}
