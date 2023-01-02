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

class ClientSettingsPacket extends InboundPacket
{

	/** @var string */
	public $lang;
	/** @var int */
	public $viewDistance;
	/** @var int */
	public $chatMode;
	/** @var bool */
	public $chatColors;
	/** @var int */
	public $displayedSkinParts;
	/** @var int */
	public $mainHand;
	/** @var bool */
	public $textFilteringEnabled;
	/** @var bool */
	public $allowsListing;

	public function pid(): int
	{
		return self::CLIENT_SETTINGS_PACKET;
	}

	protected function decode(): void
	{
		$this->lang = $this->getString();
		$this->viewDistance = $this->getSignedByte();
		$this->chatMode = $this->getVarInt();
		$this->chatColors = $this->getBool();
		$this->displayedSkinParts = $this->getByte();
		$this->mainHand = $this->getVarInt();
		$this->textFilteringEnabled = $this->getBool();
		$this->allowsListing = $this->getBool();
	}
}
