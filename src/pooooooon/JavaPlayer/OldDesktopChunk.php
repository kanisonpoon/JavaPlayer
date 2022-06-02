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

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\world\World;
use pooooooon\javaplayer\entity\ItemFrameBlockEntity;
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
  /** @var World */
  private $level;
  /** @var bool */
  private $groundUp;
  /** @var int */
  private $bitMap;
  /** @var string */
  private $biomes;
  /** @var string */
  private $chunkData;

  /**
  * @param JavaPlayerNetworkSession $player
  * @param int           $chunkX
  * @param int           $chunkZ
  */
  public function __construct(JavaPlayerNetworkSession $player, int $chunkX, int $chunkZ){
    $this->player = $player;
    $this->chunkX = $chunkX;
    $this->chunkZ = $chunkZ;
    $this->level = $player?->getPlayer();
    $this->groundUp = true;
    $this->bitMap = 0;
    $this->generateChunk();
  }

  public function generateChunk() : void{
    $chunk = $this->level->getChunk($this->chunkX, $this->chunkZ, false);
    $this->biomes = $chunk->getBiomeIdArray();

    $payload = "";
    foreach($chunk->getSubChunks() as $num => $subChunk){
      if($subChunk->isEmptyFast()){
       continue;
      }

      $this->bitMap |= 0x01 << $num;

      $palette = [];
      $bitsPerBlock = 8;

      $chunkData = "";
      for($y = 0; $y < 16; ++$y){
       for($z = 0; $z < 16; ++$z){
        $data = "";
        for($x = 0; $x < 16; ++$x){
         $Block = BlockFactory::getInstance()->fromFullBlock($subChunk->getFullBlock($x, $y, $z));
         assert($Block instanceof Block);
         $blockId = $Block->getId();
         $blockData = $Block->getMeta();

         if($blockId == BlockLegacyIds::FRAME_BLOCK){
           ItemFrameBlockEntity::getItemFrame($this->level, $x + ($this->chunkX << 4), $y + ($num << 4), $z + ($this->chunkZ << 4), $blockData, true);
           $block = BlockLegacyIds::AIR;
         }else{
           ConvertUtils::convertBlockData(true, $blockId, $blockData);
           $block = (int) ($blockId << 4) | $blockData;
         }

         if(($key = array_search($block, $palette, true)) === false){
           $key = count($palette);
           $palette[$key] = $block;
         }
         $data .= chr($key);//bit

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
    /* Bits Per Block & Palette Length */
    $payload .= Binary::writeByte($bitsPerBlock).Binary::writeJavaVarInt(count($palette));

    /* Palette */
    foreach($palette as $value){
      $payload .= Binary::writeJavaVarInt($value);
    }

    /* Data Array Length */
    $payload .= Binary::writeJavaVarInt(strlen($chunkData) / 8);

    /* Data Array */
    $payload .= $chunkData;

    /* Block Light*/
    $payload .= $blockLightData;

    /* Sky Light Only Over World */
    // if($this->player->bigBrother_getDimension() === 0){
    $payload .= $skyLightData;
    // }
    }

    $this->chunkData = $payload;
  }

  /**
  * @return bool
  */
  public function isGroundUp() : bool{
    return $this->groundUp;
  }

  /**
  * @return int
  */
  public function getBitMapData() : int{
    return $this->bitMap;
  }

  /**
  * @return string
  */
  public function getBiomesData() : string{
    return $this->biomes;
  }

  /**
  * @return string
  */
  public function getChunkData() : string{
    return $this->chunkData;
  }
}
