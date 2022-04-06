<?php

declare(strict_types=1);

namespace pooooooon\javaplayer;

use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pooooooon\javaplayer\listener\JavaPlayerListener;
use pooooooon\javaplayer\network\JavaPlayerNetworkSession;
use pooooooon\javaplayer\network\listener\ClosureJavaPlayerPacketListener;
use pooooooon\javaplayer\network\protocol\Play\Server\PlayerPositionAndLookPacket;

final class DefaultJavaPlayerListener implements JavaPlayerListener
{

	private Loader $plugin;

	public function __construct(Loader $plugin)
	{
		$this->plugin = $plugin;
	}

	public function onPlayerAdd(Player $player): void
	{
		$session = $player->getNetworkSession();
		assert($session instanceof JavaPlayerNetworkSession);

		$entity_runtime_id = $player->getId();
		$session->registerSpecificPacketListener(PlayStatusPacket::class, new ClosureJavaPlayerPacketListener(function (ClientboundPacket $packet, NetworkSession $session) use ($entity_runtime_id): void {
			assert($packet instanceof PlayStatusPacket);
			assert($session instanceof JavaPlayerNetworkSession);
			if ($packet->status === PlayStatusPacket::PLAYER_SPAWN) {
				$pk = new PlayerPositionAndLookPacket();//for loading screen
				$pk->x = $session->getPlayer()->getPosition()->getX();
				$pk->y = $session->getPlayer()->getPosition()->getY();
				$pk->z = $session->getPlayer()->getPosition()->getZ();
				$pk->yaw = 0;
				$pk->pitch = 0;
				$pk->flags = 0;
				$session->putRawPacket($pk);
				$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(static function () use ($session, $entity_runtime_id): void {
					if ($session->isConnected()) {
						$packet = new SetLocalPlayerAsInitializedPacket();
						$packet->actorRuntimeId = $entity_runtime_id;

						$serializer = PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
						$packet->encode($serializer);
						$session->handleDataPacket($packet, $serializer->getBuffer());
					}
				}), 40);
			}
		}));

		$session->registerSpecificPacketListener(RespawnPacket::class, new ClosureJavaPlayerPacketListener(function (ClientboundPacket $packet, NetworkSession $session): void {
			$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($session): void {
				if ($session->isConnected()) {
					/** @var Player $player */
					$player = $session->getPlayer();
					$player->respawn();
					$fake_player = $this->plugin->getJavaPlayer($player);
				}
			}), 40);
		}));

		$session->registerSpecificPacketListener(ChangeDimensionPacket::class, new ClosureJavaPlayerPacketListener(function (ClientboundPacket $packet, NetworkSession $session): void {
			$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($session): void {
				if ($session->isConnected()) {
					$player = $session->getPlayer();
					if ($player !== null) {
						$packet = PlayerActionPacket::create(
							$player->getId(),
							PlayerAction::DIMENSION_CHANGE_ACK,
							BlockPosition::fromVector3($player->getPosition()->floor()),
							0
						);

						$serializer = PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
						$packet->encode($serializer);
						$session->handleDataPacket($packet, $serializer->getBuffer());
					}
				}
			}), 40);
		}));
	}

	public function onPlayerRemove(Player $player): void
	{
		// not necessary to unregister listeners because they'll automatically
		// be gc-d as nothing holds ref to player object?
	}
}
