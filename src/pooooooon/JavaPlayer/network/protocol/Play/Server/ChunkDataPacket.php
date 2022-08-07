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
use pooooooon\javaplayer\utils\ConvertUtils;

class ChunkDataPacket extends OutboundPacket
{

	/** @var int */
	public $chunkX;
	/** @var int */
	public $chunkZ;
	/** @var string */
	public $heightMaps;
	
	public $isFullChunk = true;
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
		$this->putBool($this->isFullChunk);
		$this->putVarInt($this->primaryBitMask);
		$this->put(/*$this->heightMaps ?? */base64_decode("CgAADAAPTU9USU9OX0JMT0NLSU5HAAAAJRMJhMJZLJRKE0mk0mk0mkwTCWSyUSicTRNJpNJpMJhMEslEknE0mk0TSaTCYTCYSxJJpNJpNJpNEwmEwmEslEoTSaTSaTSaTRMJhLJRKJJNE0mk0mk0mEwSyUSiSTSaTRMJhMJhMJhLEokk0mk0mk0TCWSyWSyWShNJpMJhMJhMEslkolEolEoTCWSyWSyWSxKJRKJRKJhMEolEolEolEoSiUSiYTCWShJJJJJJJJJKEolkslEkkkkSCSSSSSSSSRKJJIJBIJBIEgkEkkkkkkoSCQSCQSCQSBIJJJJJJJBIEgkEgkEgkEgSCSSCQSCQSBIJBIJBIJBIEgkEgjkgkEgSCQSCQSCQSRHI5HI5HJBIEgkEgkEgkEgRyORyORyQSAAAAAI5HI5HAA=="));//heightmap
		if($this->isFullChunk){
			$this->putVarInt(strlen($this->biomes));
			$this->put($this->biomes);
		}
		$this->putVarInt(strlen($this->data));
		$this->put($this->data);
		$this->putVarInt(0);
		// foreach($this->blockEntities as $blockEntity){
		// 	$this->put(ConvertUtils::convertNBTDataFromPEtoPC(ConvertUtils::convertBlockEntity(true, $blockEntity)));
		// }
	}
}
