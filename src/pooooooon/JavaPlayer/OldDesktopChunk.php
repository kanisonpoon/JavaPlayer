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

namespace pooooooon\javaplayer;

use pocketmine\block\BlockLegacyIds as Block;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World as Level;
use pooooooon\javaplayer\entity\ItemFrameBlockEntity;
use pooooooon\javaplayer\nbt\tag\LongArrayTag;
use pooooooon\javaplayer\network\JavaPlayerNetworkSession;
use pooooooon\javaplayer\utils\ConvertUtils;
use pooooooon\javaplayer\utils\JavaBinarystream as Binary;

class OldDesktopChunk{
  /** @var JavaPlayerNetworkSession */
	private $player;
	/** @var int */
	private $chunkX;
	/** @var int */
	private $chunkZ;
	/** @var Level */
	private $level;
	/** @var int */
	private $chunkBitmask;
	/** @var bool */
	private $isFullChunk = false;
	/** @var string */
	private $chunkData;
	/** @var CompoundTag */
	private $heightMaps;
	/** @var string */
	private $biomes;
	/** @var int */
	private $skyLightBitMask;
	/** @var int */
	private $blockLightBitMask;
	/** @var string[] */
	private $skyLight = [];
	/** @var string[] */
	private $blockLight = [];
	/** @var bool */
	public $idk;
	/** @var bool */
	public $isCavesAndCliffs = false;

	/**
	 * @param JavaPlayerNetworkSession $player
	 * @param int           $chunkX
	 * @param int           $chunkZ
	 */
	public function __construct(JavaPlayerNetworkSession $player, int $chunkX, int $chunkZ){
		$this->player = $player;
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
		$this->level = $player->getPlayer()->getWorld();
		$this->chunkBitmask = 0;
		$this->idk = false;
		$this->generateChunk();
		$this->generateHeightMaps();
	}

	public function generateChunk() : void{
		$chunk = $this->level->getChunk($this->chunkX, $this->chunkZ);
		$this->isFullChunk = count($chunk->getSubChunks()) === 16;
		$this->isCavesAndCliffs = count($chunk->getSubChunks()) === 24;

		$payload = "";
		foreach($chunk->getSubChunks() as $num => $subChunk){
			if($subChunk->isEmptyFast()){
				continue;
			}
			$this->idk = true;
			$this->chunkBitmask |= (0x01 << $num);
			$this->skyLightBitMask |= (0x01 << $num + 1);
			$this->blockLightBitMask |= (0x01 << $num + 1);

			$palette = [];
			$blockCount = 0;
			$bitsPerBlock = 8;

			$chunkData = "";
			for($y = 0; $y < 16; ++$y){
				for($z = 0; $z < 16; ++$z){

					$data = "";
					for($x = 0; $x < 16; ++$x){
						$Block = $subChunk->getFullBlock($x, $y, $z);
						$blockId = $Block >> 4;
						$blockData = $Block & 0xf;

						if($blockId == Block::FRAME_BLOCK){
							ItemFrameBlockEntity::getItemFrame($this->level, $x + ($this->chunkX << 4), $y + ($num << 4), $z + ($this->chunkZ << 4), $blockData, true);
							$block = Block::AIR;
						}else{
							if($blockId !== Block::AIR){
								$blockCount++;
							}

							ConvertUtils::convertBlockData(true, $blockId, $blockData);
							$stateId = ConvertUtils::getBlockStateIndex($blockId, $blockData);
							$block = $stateId;
						}

						if(($key = array_search($block, $palette, true)) === false){
							$key = count($palette);
							$palette[$key] = $block;
						}
						$data .= chr($key);

						if($x === 7 or $x === 15){//Reset ChunkData
							$chunkData .= strrev($data);
							$data = "";
						}
					}
				}
			}

			$blockLightData = "";
			$skyLightData = "";
			for($y = 0; $y < 16; ++$y){
				for($z = 0; $z < 16; ++$z){
					for($x = 0; $x < 16; $x += 2){
						$blockLight = $this->level->getBlockLightAt($x, $y, $z) | ($this->level->getBlockLightAt($x + 1, $y, $z) << 4);
						$skyLight = $this->level->getRealBlockSkyLightAt($x, $y, $z) | ($this->level->getRealBlockSkyLightAt($x + 1, $y, $z) << 4);

						$blockLightData .= chr($blockLight);
						$skyLightData .= chr($skyLight);
					}
				}
			}
			$this->skyLight[] = $skyLightData;
			$this->blockLight[] = $blockLightData;

			/* Bits Per Block & Palette Length */
			$payload .= Binary::writeShort($blockCount).Binary::writeByte($bitsPerBlock).Binary::writeJavaVarInt(count($palette));

			/* Palette */
			foreach($palette as $value){
				$payload .= Binary::writeJavaVarInt($value);
			}

			/* Data Array Length */
			$payload .= Binary::writeJavaVarInt(strlen($chunkData) / 8);

			/* Data Array */
			$payload .= $chunkData;
		}

		$this->chunkData = $payload;
	}

	public function generateHeightMaps(){
		$chunk = $this->level->getChunk($this->chunkX, $this->chunkZ, false);

		$long = 0x00;
		$longData = [];
		$shiftCount = 0;
		foreach($chunk->getHeightMapArray() as $value){
			$long <<= 9;
			$long |= ($value & 0x1fff);
			$shiftCount++;
			if($shiftCount === 7){
				$longData[] = $long;
				$long = 0x00;
				$shiftCount = 0;
			}
		}
		$longData[] = $long;

		$heightMaps = CompoundTag::create()->setTag("MOTION_BLOCKING", new LongArrayTag($longData));
		$this->heightMaps = $heightMaps;

		$payload = "";
		for($i = 0; $i < 256; $i++){
			$payload .= Binary::writeInt(ord($chunk->getBiomeIdArray()[$i]));
		}
		$this->biomes = $payload;
	}

	/**
	 * @return int
	 */
	public function getChunkBitMask() : int{
		return $this->chunkBitmask;
	}

	/**
	 * @return bool
	 */
	public function isFullChunk(): bool{
		return $this->isFullChunk;
	}

	/**
	 * @return string
	 */
	public function getChunkData() : string{
		return $this->chunkData;
	}

	public function getHeightMaps(): CompoundTag{
		return $this->heightMaps;
	}

	public function getBiomes(): string{
		return $this->biomes;
	}

	public function getSkyLightBitMask(): int{
		return $this->skyLightBitMask;
	}

	public function getBlockLightBitMask(): int{
		return $this->blockLightBitMask;
	}

	public function getSkyLight(): array{
		return $this->skyLight;
	}

	public function getBlockLight(): array{
		return $this->blockLight;
	}
}
