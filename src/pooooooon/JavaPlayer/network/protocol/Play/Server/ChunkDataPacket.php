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

use pocketmine\block\tile\Spawnable;
use pooooooon\javaplayer\network\OutboundPacket;

class ChunkDataPacket extends OutboundPacket
{

	/** @var int */
	public $chunkX;
	/** @var int */
	public $chunkZ;
	/** @var string */
	public $heightMaps;
	/** @var string */
	public $data;
	/** @var Spawnable[] */
	public $blockEntities = [];
	/** @var bool */
	public $trustEdges = false;
	/** @var int */
	public $skyLightMask;
	/** @var int */
	public $blockLightMask;
	/** @var int */
	public $emptySkyLightMask;
	/** @var int */
	public $emptyBlockLightMask;
	/** @var string[] */
	public $skyLight;
	/** @var string[] */
	public $blockLight;

	public function pid(): int
	{
		return self::CHUNK_DATA_PACKET;
	}

	protected function encode(): void
	{
		$this->putInt($this->chunkX);
		$this->putInt($this->chunkZ);
		$this->put($this->heightMaps ?? base64_decode("CgAPTU9USU9OX0JMT0NLSU5HAA=="));//heightmap
		$this->putVarInt(strlen($this->data));
		$this->put($this->data);
		$this->putVarInt(0);//blockEntities count
		// $this->putVarInt(count($this->blockEntities));
		// foreach($this->blockEntities as $blockEntity){
		// 	$this->putByte((($blockEntity->getPosition()->getX() & 15) << 4) | $blockEntity->getPosition()->getZ() & 15);
		//     $this->putShort($blockEntity->getPosition()->getY());
		// 	$type = $blockEntity;
		// 	if ($type == null) {
		// 		$this->putVarInt(-1);
		// 	} else {
		// 		$this->putVarInt($type);
		// 	}
		// 	$this->put(ConvertUtils::convertNBTDataFromPEtoPC(ConvertUtils::convertBlockEntity(true, $blockEntity->getSpawnCompound())));
		// }
		$this->putBool($this->trustEdges);
		$this->putVarInt($this->skyLightMask);
		$this->putVarInt($this->blockLightMask);
		$this->putVarInt($this->emptySkyLightMask);
		$this->putVarInt($this->emptyBlockLightMask);
		foreach ($this->skyLight as $skyLight) {
			$this->putVarInt(strlen($skyLight));
			$this->put($skyLight);
		}
		foreach ($this->blockLight as $blockLight) {
			$this->putVarInt(strlen($blockLight));
			$this->put($blockLight);
		}
	}
}
