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

class TabCompletePacket extends OutboundPacket
{

	/** @var int */
	public $id;
	/** @var int */
	public $start;
	/** @var int */
	public $length;
	/** @var array */
	public $matches = [];

	public function pid(): int
	{
		return self::TAB_COMPLETE_PACKET;
	}

	protected function encode(): void
	{
		$this->putVarInt($this->id);
		$this->putVarInt($this->start);
		$this->putVarInt($this->length);
		$this->putVarInt(count($this->matches));
		foreach ($this->matches as $match) {
			$this->putString($match[0]);
			$this->putBool($match[1][0]);
			if ($match[1][0]) {
				$this->putString($match[1][1]);
			}
		}
	}

}
