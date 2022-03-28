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

namespace pooooooon\javaplayer\entity;

use pocketmine\block\Block;
use pocketmine\block\tile\ItemFrame;
use pocketmine\entity\Entity;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\World\Position;
use pocketmine\world\World;
use pooooooon\javaplayer\network\JavaPlayerNetworkSession;
use pooooooon\javaplayer\network\protocol\Play\Server\DestroyEntitiesPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityMetadataPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SpawnEntityPacket;
use pooooooon\javaplayer\utils\ConvertUtils;
use Ramsey\Uuid\Uuid;

class ItemFrameBlockEntity extends Position
{
	/** @var array */
	protected static $itemFrames = [];
	/** @var array */
	protected static $itemFramesAt = [];
	/** @var array */
	protected static $itemFramesInChunk = [];

	/** @var array */
	private static $mapping = [
		0 => [-90, 3],//EAST
		1 => [+90, 1],//WEST
		2 => [0, 0],//SOUTH
		3 => [-180, 2] //NORTH
	];

	/** @var int */
	private $eid;
	/** @var string */
	private $uuid;
	/** @var int */
	private $facing;
	/** @var int */
	private $yaw;

	/**
	 * @param World $world
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $data
	 * @throws
	 */
	private function __construct(World $world, int $x, int $y, int $z, int $data)
	{
		parent::__construct($x, $y, $z, $world);
		$this->eid = Entity::nextRuntimeId();
		$this->uuid = Uuid::uuid4()->getBytes();
		$this->facing = $data;
		$this->yaw = self::$mapping[$data][0] ?? 0;
	}

	/**
	 * @param World $level
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return bool
	 */
	public static function exists(World $level, int $x, int $y, int $z): bool
	{
		return isset(self::$itemFramesAt[$level->getId()][World::blockHash($x, $y, $z)]);
	}

	/**
	 * @param World $level
	 * @param int $eid
	 * @return ItemFrameBlockEntity|null
	 */
	public static function getItemFrameById(World $level, int $eid): ?ItemFrameBlockEntity
	{
		return self::$itemFrames[$level->getId()][$eid] ?? null;
	}

	/**
	 * @param Block $block
	 * @param bool $create
	 * @return ItemFrameBlockEntity|null
	 */
	public static function getItemFrameByBlock(Block $block, bool $create = false): ?ItemFrameBlockEntity
	{
		return self::getItemFrame($block->getPosition()->getWorld(), $block->getPosition()->x, $block->getPosition()->y, $block->getPosition()->z, $block->getMeta(), $create);
	}

	/**
	 * @param World $level
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $data
	 * @param bool $create
	 * @return ItemFrameBlockEntity|null
	 */
	public static function getItemFrame(World $level, int $x, int $y, int $z, int $data = 0, bool $create = false): ?ItemFrameBlockEntity
	{
		$entity = null;

		if (isset(self::$itemFramesAt[$level_id = $level->getId()][$index = World::blockHash($x, $y, $z)])) {
			$entity = self::$itemFramesAt[$level_id][$index];
		} elseif ($create) {
			$entity = new ItemFrameBlockEntity($level, $x, $y, $z, $data);
			self::$itemFrames[$level_id][$entity->eid] = $entity;
			self::$itemFramesAt[$level_id][$index] = $entity;

			if (!isset(self::$itemFramesInChunk[$level_id][$index = World::chunkHash($x >> 4, $z >> 4)])) {
				self::$itemFramesInChunk[$level_id][$index] = [];
			}
			self::$itemFramesInChunk[$level_id][$index] [] = $entity;
		}

		return $entity;
	}

	/**
	 * @param World $level
	 * @param int $x
	 * @param int $z
	 * @return array
	 */
	public static function getItemFramesInChunk(World $level, int $x, int $z): array
	{
		return self::$itemFramesInChunk[$level->getId()][World::chunkHash($x, $z)] ?? [];
	}

	/**
	 * @return int
	 */
	public function getEntityId(): int
	{
		return $this->eid;
	}

	/**
	 * @return int
	 */
	public function getFacing(): int
	{
		return $this->facing;
	}

	/**
	 * @return bool
	 */
	public function hasItem(): bool
	{
		$tile = $this->getWorld()->getTile($this);
		if ($tile instanceof ItemFrame) {
			return $tile->hasItem();
		}

		return false;
	}

	/**
	 * @param JavaPlayerNetworkSession $player
	 */
	public function spawnTo(JavaPlayerNetworkSession $player)
	{
		$pk = new SpawnEntityPacket();
		$pk->eid = $this->eid;
		$pk->uuid = $this->uuid;
		$pk->type = SpawnEntityPacket::ITEM_FRAMES;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->yaw = $this->yaw;
		$pk->pitch = 0;
		$pk->data = self::$mapping[$this->facing][1];
		$pk->sendVelocity = true;
		$pk->velocityX = 0;
		$pk->velocityY = 0;
		$pk->velocityZ = 0;
		$player->putRawPacket($pk);

		$pk = new EntityMetadataPacket();
		$pk->entityId = $this->eid;
		$pk->metadata = ["convert" => true];

		$tile = $this->getWorld()->getTile($this);
		if ($tile instanceof ItemFrame) {
			$item = $tile->hasItem() ? $tile->getItem() : ItemFactory::air();

			if ($item->getId() === ItemIds::FILLED_MAP) {
				$mapId = $item->getNamedTag()->getLong("map_uuid");
				if ($mapId !== null) {
					// store $mapId as meta
					//$item->setDamage($mapId);

					$req = new MapInfoRequestPacket();
					$req->mapId = $mapId;
					$serializer = PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
					$req->encode($serializer);
					$player->handleDataPacket($req, $serializer->getBuffer());
				}
			}

			ConvertUtils::convertItemData(true, $item);
			$pk->metadata[6] = [5, $item];
			$pk->metadata[7] = [1, $tile->getItemRotation()];
		}

		//$player->putRawPacket($pk);
	}

	public function despawnFromAll(): void
	{
		foreach ($this->getWorld()->getChunkLoaders($this->x >> 4, $this->z >> 4) as $player) {
			if ($player instanceof JavaPlayerNetworkSession) {
				$this->despawnFrom($player);
			}
		}
		self::removeItemFrame($this);
	}

	/**
	 * @param JavaPlayerNetworkSession $player
	 */
	public function despawnFrom(JavaPlayerNetworkSession $player): void
	{
		$pk = new DestroyEntitiesPacket();
		$pk->ids [] = $this->eid;
		$player->putRawPacket($pk);
	}

	/**
	 * @param ItemFrameBlockEntity $entity
	 */
	public static function removeItemFrame(ItemFrameBlockEntity $entity): void
	{
		unset(self::$itemFrames[$entity->getWorld()->getid()][$entity->eid]);
		unset(self::$itemFramesAt[$entity->getWorld()->getId()][World::blockHash($entity->x, $entity->y, $entity->z)]);
		if (isset(self::$itemFramesInChunk[$level_id = $entity->getWorld()->getId()][$index = World::chunkHash($entity->x >> 4, $entity->z >> 4)])) {
			self::$itemFramesInChunk[$level_id][$index] = array_diff(self::$itemFramesInChunk[$level_id][$index], [$entity]);
		}
	}
}