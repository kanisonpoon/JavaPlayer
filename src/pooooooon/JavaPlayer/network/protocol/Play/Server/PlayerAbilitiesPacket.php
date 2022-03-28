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

class PlayerAbilitiesPacket extends OutboundPacket
{

	/** @var bool */
	public $damageDisabled = false;
	/** @var bool */
	public $canFly = false;
	/** @var bool */
	public $isFlying = false;
	/** @var bool */
	public $isCreative = false;

	/** @var float */
	public $flyingSpeed;
	/** @var float */
	public $viewModifierField;

	public function pid(): int
	{
		return self::PLAYER_ABILITIES_PACKET;
	}

	protected function encode(): void
	{
		$flags = 0;
		if ($this->isCreative) {
			$flags |= 0x08;
		}
		if ($this->isFlying) {
			$flags |= 0x02;
		}
		if ($this->canFly) {
			$flags |= 0x04;
		}
		if ($this->damageDisabled) {
			$flags |= 0x01;
		}
		$this->putByte($flags);
		$this->putFloat($this->flyingSpeed);
		$this->putFloat($this->viewModifierField);
	}

}
