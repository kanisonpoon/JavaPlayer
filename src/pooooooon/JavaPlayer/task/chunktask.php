<?php
/**
 *
 */

declare(strict_types=1);

namespace pooooooon\javaplayer\task;

use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\format\SubChunk;
use pooooooon\javaplayer\entity\ItemFrameBlockEntity;
use pooooooon\javaplayer\nbt\tag\LongArrayTag;
use pooooooon\javaplayer\nbt\TAG_Compound;
use pooooooon\javaplayer\nbt\TAG_Long_Array;
use pooooooon\javaplayer\network\JavaPlayerNetworkSession;
use pooooooon\javaplayer\network\protocol\Play\Server\ChunkDataPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\UpdateLightPacket;
use pooooooon\javaplayer\utils\ConvertUtils;
use pooooooon\javaplayer\utils\JavaBinarystream as Binary;

class chunktask extends AsyncTask
{
	/** @var string */
	public $chunk;
	/** @var int */
	public $chunkX;
	/** @var int */
	public $chunkZ;
	/** @var string */
	public $biomes;
	/** @var string */
	public $data;
	/** @var Compressor */
	protected $compressor;
	/** @var int */
	protected $chunkBitmask;
	/** @var int */
	protected $skyLightBitMask;
	/** @var int */
	protected $blockLightBitMask;

	public function __construct(int $chunkX, int $chunkZ, Chunk $chunk, JavaPlayerNetworkSession $player, Compressor $compressor)
	{
		$this->compressor = $compressor;
		$this->chunk = FastChunkSerializer::serializeTerrain($chunk);
		$this->storeLocal("player", $player);
		$this->chunkBitmask = 0;
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
	}

	public function onRun(): void
	{
		if ($this->chunk !== null) {
			$chunk = FastChunkSerializer::deserializeTerrain($this->chunk);
			$isFullChunk = count($chunk->getSubChunks()) === 16;
			$biomes = $chunk->getBiomeIdArray();

			$payload = "";

			foreach ($chunk->getSubChunks() as $num => $subChunk) {
				if ($subChunk->isEmptyFast()) {
					continue;
				}
				$this->chunkBitmask |= (0x01 << $num);
				$this->skyLightBitMask |= (0x01 << $num + 1);
				$this->blockLightBitMask |= (0x01 << $num + 1);

				$palette = [];
				$blockCount = 0;
				$bitsPerBlock = 8;

				$chunkData = "";
				for ($y = 0; $y < 16; ++$y) {
					for ($z = 0; $z < 16; ++$z) {
						$data = "";
						for ($x = 0; $x < 16; ++$x) {
							$Block = $subChunk->getFullBlock($x, $y, $z);
							$blockId = $Block >> 4;
							$blockData = $Block & 0xf;

							if ($blockId == BlockLegacyIds::FRAME_BLOCK) {
								ItemFrameBlockEntity::getItemFrame($this->fetchLocal("player")->getWorld(), $x + ($this->chunkX << 4), $y + ($num << 4), $z + ($this->chunkZ << 4), $blockData, true);
							        $block = BlockLegacyIds::AIR;
							} else {
								if ($blockId !== BlockLegacyIds::AIR) {
									$blockCount++;
								}
								ConvertUtils::convertBlockData(true, $blockId, $blockData);
								$stateId = ConvertUtils::getBlockStateIndex($blockId, $blockData);
								$block = $stateId;
							}

							if (($key = array_search($block, $palette, true)) === false) {
								$key = count($palette);
								$palette[$key] = $block;
							}
							$data .= chr($key);

							if ($x === 7 or $x === 15) {//Reset ChunkData
								$chunkData .= strrev($data);
								$data = "";
							}
						}
					}
				}

				$blockLightData = "";
				$skyLightData = "";
				for ($y = 0; $y < 16; ++$y) {
					for ($z = 0; $z < 16; ++$z) {
						for ($x = 0; $x < 16; $x += 2) {
							$blockLightData .= chr($subChunk->getBlockLightArray()->get($x, $y, $z) | ($subChunk->getBlockLightArray()->get($x + 1, $y, $z) << 4));
							$skyLightData .= chr($subChunk->getBlockSkyLightArray()->get($x, $y, $z) | ($subChunk->getBlockSkyLightArray()->get($x + 1, $y, $z) << 4));
						}
					}
				}
				$skyLight1[] = $skyLightData;
				$blockLight1[] = $blockLightData;

				/* Bits Per Block & Palette Length */
				$payload .= Binary::writeShort($blockCount) . Binary::writeByte($bitsPerBlock) . Binary::writeJavaVarInt(count($palette));

				/* Palette */
				foreach ($palette as $value) {
					$payload .= Binary::writeJavaVarInt($value);
				}

				/* Data Array Length */
				$payload .= Binary::writeJavaVarInt(strlen($chunkData) / 8);

				/* Data Array */
				$payload .= $chunkData;//todo:fix this
			}

			$chunkData = $payload;

			$long = 0x00;
			$longData = [];
			$shiftCount = 0;
			foreach ($chunk->getHeightMapArray() as $value) {
				$long <<= 9;
				$long |= ($value & 0x1fff);
				$shiftCount++;
				if ($shiftCount === 7) {
					$longData[] = $long;
					$long = 0x00;
					$shiftCount = 0;
				}
			}
			$longData[] = $long;
			$heightMaps = new TAG_Compound("", [new TAG_Long_Array("MOTION_BLOCKING", $longData)]);
			$heightMaps = $heightMaps->nbtSerialize();
			$biomepayload = "";
			for ($i = 0; $i < 256; $i++) {
				$biomepayload .= Binary::writeInt(ord($biomes[$i]));
			}
			$packets = [];
			$pk = new UpdateLightPacket();
			$pk->chunkX = $this->chunkX;
			$pk->chunkZ = $this->chunkZ;
			$pk->skyLightMask = $this->skyLightBitMask;
			$pk->blockLightMask = $this->blockLightBitMask;
			$pk->emptySkyLightMask = ~$this->skyLightBitMask;
			$pk->emptyBlockLightMask = ~$this->blockLightBitMask;
			$pk->skyLight = $skyLight1;
			$pk->blockLight = $blockLight1;
			$packets[] = $pk->getBuffer();
			// foreach (FastChunkSerializer::deserializeTerrain($this->chunk)->getTiles() as $tile) {
			// 	if ($tile instanceof Spawnable) {
			// 		$blockEntities[] = clone $tile->getSpawnCompound();
			// 	}
			// }
			$pk = new ChunkDataPacket();
			$pk->chunkX = $this->chunkX;
			$pk->chunkZ = $this->chunkZ;
			$pk->isFullChunk = $isFullChunk;
			$pk->primaryBitMask = $this->chunkBitmask;
			$pk->heightMaps = $heightMaps;
			$pk->biomes = $biomepayload;
			$pk->data = $chunkData;
			// $pk->blockEntities = $blockEntities;
			$packets[] = $pk->getBuffer();
			$this->data = $this->compressor->compress(igbinary_serialize($packets));
		}
	}

	public function onCompletion(): void
	{
		$player = $this->fetchLocal("player");
		if ($player instanceof JavaPlayerNetworkSession && $player->getPlayer()->isConnected()) {
			$data = igbinary_unserialize($this->compressor->decompress($this->data));
			if ($data !== null) {
				$ida = [0x23, 0x20];
				foreach ($data as $id => $rawdata) {
					$player->putBufferPacket($ida[$id], $rawdata);
				}
			}
		}
	}
}
