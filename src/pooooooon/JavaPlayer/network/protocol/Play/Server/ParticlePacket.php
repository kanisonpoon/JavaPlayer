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

class ParticlePacket extends OutboundPacket
{

	/** @var int */
	public $particleId;
	/** @var bool */
	public $longDistance = false;
	/** @var float */
	public $x;
	/** @var float */
	public $y;
	/** @var float */
	public $z;
	/** @var float */
	public $offsetX;
	/** @var float */
	public $offsetY;
	/** @var float */
	public $offsetZ;
	/** @var float */
	public $particleData;
	/** @var int */
	public $particleCount;
	/** @var string */
	public $data = [];

	public function pid(): int
	{
		return self::PARTICLE_PACKET;
	}

	protected function encode(): void
	{
		$this->putInt($this->particleId);
		$this->putBool($this->longDistance);
		$this->putDouble($this->x);
		$this->putDouble($this->y);
		$this->putDouble($this->z);
		$this->putFloat($this->offsetX);
		$this->putFloat($this->offsetY);
		$this->putFloat($this->offsetZ);
		$this->putFloat($this->particleData);
		$this->putInt($this->particleCount);
		$this->put($this->data);//TODO: なんとかする // .= ?????????????????
	}

}
