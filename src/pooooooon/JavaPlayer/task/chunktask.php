<?php
/**
 *
 */

declare(strict_types=1);

namespace pooooooon\javaplayer\task;

use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pooooooon\javaplayer\entity\ItemFrameBlockEntity;
use pooooooon\javaplayer\nbt\tag\LongArrayTag;
use pooooooon\javaplayer\network\JavaPlayerNetworkSession;
use pooooooon\javaplayer\network\protocol\Play\Server\ChunkDataPacket;
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

	public function __construct(int $chunkX, int $chunkZ, Chunk $chunk, JavaPlayerNetworkSession $player)
	{
		$this->chunk = FastChunkSerializer::serializeTerrain($chunk);
		$this->storeLocal("player", $player);
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
				$chunkBitmask = 0;
				$chunkBitmask |= (0x01 << $num);
				$skyLightBitMask = 0;
				$skyLightBitMask |= (0x01 << $num + 1);
				$blockLightBitMask = 0;
				$blockLightBitMask |= (0x01 << $num + 1);

				$palette = [];
				$blockCount = 0;
				$bitsPerBlock = 8;

				$chunkData = "";
				for ($y = 0; $y < 16; ++$y) {
					for ($z = 0; $z < 16; ++$z) {

						$data = "";
						for ($x = 0; $x < 16; ++$x) {
							$blockId = $subChunk->getFullBlock($x, $y, $z);
							$blockData = $subChunk->getFullBlock($x, $y, $z);

							if ($blockId == BlockLegacyIds::FRAME_BLOCK) {
								$block = ItemFrameBlockEntity::getItemFrame($this->fetchLocal("player")->getWorld(), $x + ($this->chunkX << 4), $y + ($num << 4), $z + ($this->chunkZ << 4), $blockData, true);
//                                $block = BlockLegacyIds::AIR;
							} else {
								if ($blockId !== BlockLegacyIds::AIR) {
									$blockCount++;
								}
//                                $block = $blockId;
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
							$blockLight = 0;
							$skyLight = 0;
							foreach ($subChunk->getBlockSkyLightArray() as $light) {
								$blockLight = $light->get($x, $y, $z) | ($light->get($x, $y, $z) << 4);
							}
							foreach ($subChunk->getBlockLightArray() as $light) {
								$skyLight = $light->get($x, $y, $z) | ($light->get($x, $y, $z) << 4);
							}
							$blockLightData .= chr($blockLight);
							$skyLightData .= chr($skyLight);
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
				$payload .= $chunkData;
				$payload .= $biomes;
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

			foreach ((new LongArrayTag($longData))->getValue() as $value) {
				$heightMaps = CompoundTag::create()->setLong("MOTION_BLOCKING", $value);
			}

			$payload1 = "";
			for ($i = 0; $i < 256; $i++) {
				$payload1 .= Binary::writeInt(ord($chunk->getBiomeIdArray()[$i]));
			}
			$biomes = $payload1;
			$blockEntities = [];
			foreach ($chunk->getTiles() as $tile) {
				if ($tile instanceof Spawnable) {
					$blockEntities[] = clone $tile->getSpawnCompound();
				}
			}

			$pk = new ChunkDataPacket();
			$pk->chunkX = $this->chunkX;
			$pk->chunkZ = $this->chunkZ;
			$pk->isFullChunk = $isFullChunk;
			$pk->primaryBitMask = $chunkBitmask;
			$pk->heightMaps = $heightMaps;
			$pk->data = $chunkData;
			$pk->blockEntities = $blockEntities;
			$pk->skyLightMask = $skyLightBitMask;
			$pk->blockLightMask = $blockLightBitMask;
			$pk->emptySkyLightMask = ~$skyLightBitMask;
			$pk->emptyBlockLightMask = ~$blockLightBitMask;
			$pk->skyLight = $skyLight1;
			$pk->blockLight = $blockLight1;
			$packets[] = $pk;
			$this->data = igbinary_serialize($packets);
		}
	}

	public function onCompletion(): void
	{
		$player = $this->fetchLocal("player");
		if ($player instanceof JavaPlayerNetworkSession && $player->getPlayer()->isConnected()) {
			$data = igbinary_unserialize($this->data);
			if ($data !== null) {
				foreach ($data as $pk) {
					$player->putRawPacket($pk);
				}
			}
		}
	}
}