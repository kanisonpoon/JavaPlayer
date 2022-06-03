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

namespace pooooooon\javaplayer\network;

use Exception;
use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\network\mcpe\StandardPacketBroadcaster;
use pocketmine\network\NetworkInterface;
use pocketmine\network\SourceInterface;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pooooooon\javaplayer\FakePacketSender;
use pooooooon\javaplayer\Loader;
use pooooooon\javaplayer\network\protocol\Login\EncryptionResponsePacket;
use pooooooon\javaplayer\network\protocol\Login\LoginStartPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\AdvancementTabPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\AnimationPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\ChatMessagePacket;
use pooooooon\javaplayer\network\protocol\Play\Client\ClickWindowPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\ClientSettingsPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\ClientStatusPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\CloseWindowPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\CraftRecipeRequestPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\CreativeInventoryActionPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\EntityActionPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\HeldItemChangePacket;
use pooooooon\javaplayer\network\protocol\Play\Client\InteractEntityPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\KeepAlivePacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerAbilitiesPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerBlockPlacementPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerDiggingPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerMovementPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerPositionAndRotationPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerPositionPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PlayerRotationPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\PluginMessagePacket;
use pooooooon\javaplayer\network\protocol\Play\Client\TabCompletePacket;
use pooooooon\javaplayer\network\protocol\Play\Client\TeleportConfirmPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\UpdateSignPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\UseItemPacket;
use pooooooon\javaplayer\network\protocol\Play\Client\WindowConfirmationPacket;
use pooooooon\javaplayer\utils\JavaBinarystream as Binary;
use ReflectionClass;
use ReflectionException;
use SplObjectStorage;
use Throwable;

class ProtocolInterface implements NetworkInterface
{
	/** @var Loader */
	protected $plugin;
	/** @var Server */
	protected $server;
	/** @var Translator */
	protected $translator;
	/** @var InfoThread */
	protected $thread;
	/** @var Packet[][] */
	private static $packet;
	/** @var int[][] */
	private static $packetId;

	/**
	 * @var SplObjectStorage<int>
	 * @phpstan-var SplObjectStorage<int>|SplObjectStorage
	 */
	protected $sessions;

	/** @var JavaPlayerNetworkSession[] */
	protected $sessionsPlayers = [];

	/** @var int */
	private $threshold;

	/**
	 * @param BigBrother $plugin
	 * @param Server $server
	 * @param Translator $translator
	 * @param int $threshold
	 * @throws Exception
	 */
	public function __construct(Loader $plugin, Server $server, Translator $translator, int $threshold, int $port, string $ip, string $motd = "Minecraft: PE server", ?string $icon = null)
	{
		$this->plugin = $plugin;
		$this->server = $server;
		$this->translator = $translator;
		$this->threshold = $threshold;
		$this->thread = new InfoThread($server->getLogger(), $server->getLoader(), $port, $ip, $motd, $icon);
		$this->sessions = new SplObjectStorage();
		$this->PacketList();
	}

	/**
	 * @override
	 */
	public function start(): void
	{
		$this->thread->start();
	}

	/**
	 * @override
	 */
	public function emergencyShutdown()
	{
		$this->thread->pushMainToThreadPacket(chr(InfoManager::PACKET_EMERGENCY_SHUTDOWN));
	}

	/**
	 * @override
	 */
	public function shutdown(): void
	{
		$this->thread->pushMainToThreadPacket(chr(InfoManager::PACKET_SHUTDOWN));
		$this->thread->join();
	}

	/**
	 * @param string $name
	 * @override
	 */
	public function setName(string $name): void
	{
		$info = Server::getInstance()->getQueryInformation();
		$value = [
			"MaxPlayers" => $info->getMaxPlayerCount(),
			"OnlinePlayers" => $info->getPlayerCount(),
		];
		$buffer = chr(InfoManager::PACKET_SET_OPTION) . chr(strlen("name")) . "name" . json_encode($value);
		$this->thread->pushMainToThreadPacket($buffer);
	}

	/**
	 * @param Player $player
	 * @param string $reason
	 * @override
	 */
	public function close(Player $player, string $reason = "unknown reason")
	{
		if (isset($this->sessions[$player->getNetworkSession()])) {
			/** @var int $identifier */
			$identifier = $this->sessions[$player->getNetworkSession()];
			$this->sessions->detach($player->getNetworkSession());
			$this->thread->pushMainToThreadPacket(chr(InfoManager::PACKET_CLOSE_SESSION) . Binary::writeInt($identifier));
		}
	}

	/**
	 * @param JavaPlayerNetworkSession $player
	 */
	public function setCompression(JavaPlayerNetworkSession $player)
	{
		if (isset($this->sessions[$player])) {
			/** @var int $target */
			$target = $this->sessions[$player];
			$data = chr(infoManager::PACKET_SET_COMPRESSION) . Binary::writeInt($target) . Binary::writeInt($this->threshold);
			$this->thread->pushMainToThreadPacket($data);
		}
	}

	/**
	 * @param JavaPlayerNetworkSession $player
	 * @param string $secret
	 */
	public function enableEncryption(JavaPlayerNetworkSession $player, string $secret)
	{
		if (isset($this->sessions[$player])) {
			/** @var int $target */
			$target = $this->sessions[$player];
			$data = chr(InfoManager::PACKET_ENABLE_ENCRYPTION) . Binary::writeInt($target) . $secret;
			$this->thread->pushMainToThreadPacket($data);
		}
	}

	/**
	 * @param JavaPlayerNetworkSession $player
	 * @param Packet $packet
	 */
	public function putRawPacket(JavaPlayerNetworkSession $player, Packet $packet)
	{
		if (isset($this->sessions[$player])) {
			/** @var int $target */
			$target = $this->sessions[$player];
			$this->sendPacket($target, $packet);
		}
	}

	/**
	 * @param int $target
	 * @param Packet $packet
	 */
	protected function sendPacket(int $target, Packet $packet)
	{
		if (false) {
			if ($packet->pid() !== OutboundPacket::KEEP_ALIVE_PACKET) {
				try {
					echo "[Send][Interface] 0x" . bin2hex(chr($packet->pid())) . ": " . strlen($packet->write()) . "\n";
					echo (new ReflectionClass($packet))->getName() . "\n";
				} catch (ReflectionException $e) {
				}
			}
		}

		try {
			$data = chr(InfoManager::PACKET_SEND_PACKET) . Binary::writeInt($target) . $packet->write();
			$this->thread->pushMainToThreadPacket($data);
		} catch (Throwable $t) {
		}
	}

	/**
	 * @override
	 */
	public function tick(): void
	{
		while (is_string($buffer = $this->thread->readThreadToMainPacket())) {
			$offset = 1;
			$pid = ord($buffer[0]);

			if ($pid === InfoManager::PACKET_SEND_PACKET) {
				$id = Binary::readInt(substr($buffer, $offset, 4));
				$offset += 4;
				if (isset($this->sessionsPlayers[$id])) {
					$payload = substr($buffer, $offset);
					try {
						$this->handlePacket($this->sessionsPlayers[$id], $payload);
					} catch (Exception $e) {
						if (false) {
							$logger = $this->server->getLogger();
							if ($logger instanceof MainLogger) {
								$logger->debug("DesktopPacket 0x" . bin2hex($payload));
								$logger->logException($e);
							}
						}
					}
				}
			} elseif ($pid === InfoManager::PACKET_OPEN_SESSION) {
				$id = Binary::readInt(substr($buffer, $offset, 4));
				$offset += 4;
				if (isset($this->sessionsPlayers[$id])) {
					continue;
				}
				$len = ord($buffer[$offset++]);
				$address = substr($buffer, $offset, $len);
				$offset += $len;
				$port = Binary::readShort(substr($buffer, $offset, 2));

				$compressor = ZlibCompressor::getInstance();
				assert($compressor instanceof ZlibCompressor);

				$session = new JavaPlayerNetworkSession(Server::getInstance(), Server::getInstance()->getNetwork()->getSessionManager(), PacketPool::getInstance(), new FakePacketSender(), new StandardPacketBroadcaster(Server::getInstance()), $compressor, $address, $port, $this->plugin);
				Server::getInstance()->getNetwork()->getSessionManager()->add($session);
				$this->sessions->attach($session, $id);
				$this->sessionsPlayers[$id] = $session;

				/*$player = new DesktopPlayer($this, $identifier, $address, $port, $this->plugin);
				$this->sessions->attach($player, $id);
				$this->sessionsPlayers[$id] = $player;
				$this->plugin->getServer()->addPlayer($player);*/
				//TODO
			} elseif ($pid === InfoManager::PACKET_CLOSE_SESSION) {
				$id = Binary::readInt(substr($buffer, $offset, 4));
				if (!isset($this->sessionsPlayers[$id])) {
					continue;
				}
				$player = $this->sessionsPlayers[$id];
				$player->disconnect("");
				Server::getInstance()->getNetwork()->getSessionManager()->remove($player);
				$this->closeSession($id);
			}
		}
	}

	/**
	 * @param JavaPlayerNetworkSession $player
	 * @param string $payload
	 */
	protected function handlePacket(JavaPlayerNetworkSession $player, string $payload)
	{
		if (false) {
			if (ord($payload[0]) !== InboundPacket::KEEP_ALIVE_PACKET) {//KeepAlivePacket
				echo "[Receive][Interface] 0x" . bin2hex(chr(ord($payload[0]))) . "\n";
			}
		}

		$pid = ord($payload[0]);
		$offset = 1;

		$status = $player->status;

		if ($status === 1) {
			$pk = self::getPacket($pid, true);
			if($pk == null){
				echo "[Receive][Interface] 0x" . bin2hex(chr($pid)) . " Not implemented\n";
				return;
			}
			$pk->read($payload, $offset);
			$this->receivePacket($player, $pk);
		} elseif ($status === 0) {
			if ($pid === 0x00) {
				$pk = new LoginStartPacket();
				$pk->read($payload, $offset);
				$player->bigBrother_handleAuthentication($pk->name, true);
			} elseif ($pid === 0x01 && false) {
				$pk = new EncryptionResponsePacket();
				$pk->read($payload, $offset);
				$player->bigBrother_processAuthentication($pk);
			} else {
				$player->disconnect("Unexpected packet $pid", true);
			}
		}
	}
	
	private function PacketList(){
		$packet = [];
		$packet[InboundPacket::TELEPORT_CONFIRM_PACKET][true] = new TeleportConfirmPacket();
		$packet[InboundPacket::CHAT_MESSAGE_PACKET][true] = new ChatMessagePacket();
		$packet[InboundPacket::CLIENT_STATUS_PACKET][true] = new ClientStatusPacket();
		$packet[InboundPacket::CLIENT_SETTINGS_PACKET][true] = new ClientSettingsPacket();
		$packet[InboundPacket::TAB_COMPLETE_PACKET][true] = new TabCompletePacket();
		$packet[InboundPacket::WINDOW_CONFIRMATION_PACKET][true] = new WindowConfirmationPacket();
		$packet[InboundPacket::CLICK_WINDOW_PACKET][true] = new ClickWindowPacket();
		$packet[InboundPacket::CLOSE_WINDOW_PACKET][true] = new CloseWindowPacket();
		$packet[InboundPacket::PLUGIN_MESSAGE_PACKET][true] = new PluginMessagePacket();
		$packet[InboundPacket::INTERACT_ENTITY_PACKET][true] = new InteractEntityPacket();
		$packet[InboundPacket::KEEP_ALIVE_PACKET][true] = new KeepAlivePacket();
		$packet[InboundPacket::PLAYER_POSITION_PACKET][true] = new PlayerPositionPacket();
		$packet[InboundPacket::PLAYER_POSITION_AND_ROTATION_PACKET][true] = new PlayerPositionAndRotationPacket();
		$packet[InboundPacket::PLAYER_ROTATION_PACKET][true] = new PlayerRotationPacket();
		$packet[InboundPacket::PLAYER_MOVEMENT_PACKET][true] = new PlayerMovementPacket();
		$packet[InboundPacket::CRAFT_RECIPE_REQUEST_PACKET][true] = new CraftRecipeRequestPacket();
		$packet[InboundPacket::PLAYER_ABILITIES_PACKET][true] = new PlayerAbilitiesPacket();
		$packet[InboundPacket::PLAYER_DIGGING_PACKET][true] = new PlayerDiggingPacket();
		$packet[InboundPacket::ENTITY_ACTION_PACKET][true] = new EntityActionPacket();
		$packet[InboundPacket::ADVANCEMENT_TAB_PACKET][true] = new AdvancementTabPacket();
		$packet[InboundPacket::HELD_ITEM_CHANGE_PACKET][true] = new HeldItemChangePacket();
		$packet[InboundPacket::CREATIVE_INVENTORY_ACTION_PACKET][true] = new CreativeInventoryActionPacket();
		$packet[InboundPacket::UPDATE_SIGN_PACKET][true] = new UpdateSignPacket();
		$packet[InboundPacket::ANIMATION_PACKET][true] = new AnimationPacket();
		$packet[InboundPacket::PLAYER_BLOCK_PLACEMENT_PACKET][true] = new PlayerBlockPlacementPacket();
		$packet[InboundPacket::USE_ITEM_PACKET][true] = new UseItemPacket();
		$packet[InboundPacket::LOGIN_START_PACKET][true] = new LoginStartPacket();
		$packet[InboundPacket::ENCRYPTION_RESPONSE_PACKET][true] = new EncryptionResponsePacket();

		/*$packet[OutboundPacket::LOGIN_DISCONNECT_PACKET][false] = new LoginDisconnectPacket();
		$packet[OutboundPacket::ENCRYPTION_REQUEST_PACKET][false] = new EncryptionRequestPacket();
		$packet[OutboundPacket::LOGIN_SUCCESS_PACKET][false] = new LoginSuccessPacket();
		$packet[OutboundPacket::SPAWN_ENTITY_PACKET][false] = new SpawnEntityPacket();
		$packet[OutboundPacket::SPAWN_EXPERIENCE_ORB_PACKET][false] = new SpawnExperienceOrbPacket();
		$packet[OutboundPacket::SPAWN_LIVING_ENTITY_PACKET][false] = new SpawnLivingEntityPacket();
		$packet[OutboundPacket::SPAWN_PAINTING_PACKET][false] = new SpawnPaintingPacket();
		$packet[OutboundPacket::SPAWN_PLAYER_PACKET][false] = new SpawnPlayerPacket();
		$packet[OutboundPacket::ENTITY_ANIMATION_PACKET][false] = new EntityAnimationPacket();
		$packet[OutboundPacket::STATISTICS_PACKET][false] = new StatisticsPacket();
		$packet[OutboundPacket::BLOCK_BREAK_ANIMATION_PACKET][false] = new BlockBreakAnimationPacket();
		$packet[OutboundPacket::BLOCK_ENTITY_DATA_PACKET][false] = new BlockEntityDataPacket();
		$packet[OutboundPacket::BLOCK_ACTION_PACKET][false] = new BlockActionPacket();
		$packet[OutboundPacket::BLOCK_CHANGE_PACKET][false] = new BlockChangePacket();
		$packet[OutboundPacket::BOSS_BAR_PACKET][false] = new BossBarPacket();
		$packet[OutboundPacket::SERVER_DIFFICULTY_PACKET][false] = new ServerDifficultyPacket();
		$packet[OutboundPacket::CHAT_MESSAGE_PACKET][false] = new ServerChatMessagePacket();
		$packet[OutboundPacket::CLEAR_TITLES_PACKET][false] = new ClearTitlesPacket();
		$packet[OutboundPacket::TAB_COMPLETE_PACKET][false] = new ServerTabCompletePacket();
		$packet[OutboundPacket::CLOSE_WINDOW_PACKET][false] = new ServerCloseWindowPacket();
		$packet[OutboundPacket::WINDOW_ITEMS_PACKET][false] = new WindowItemsPacket();
		$packet[OutboundPacket::WINDOW_PROPERTY_PACKET][false] = new WindowPropertyPacket();
		$packet[OutboundPacket::SET_SLOT_PACKET][false] = new SetSlotPacket();
		$packet[OutboundPacket::PLUGIN_MESSAGE_PACKET][false] = new ServerPluginMessagePacket();
		$packet[OutboundPacket::NAMED_SOUND_EFFECT_PACKET][false] = new NamedSoundEffectPacket();
		$packet[OutboundPacket::DISCONNECT_PACKET][false] = new PlayDisconnectPacket();
		$packet[OutboundPacket::EXPLOSION_PACKET][false] = new ExplosionPacket();
		$packet[OutboundPacket::UNLOAD_CHUNK_PACKET][false] = new UnloadChunkPacket();
		$packet[OutboundPacket::CHANGE_GAME_STATE_PACKET][false] = new ChangeGameStatePacket();
		$packet[OutboundPacket::KEEP_ALIVE_PACKET][false] = new ServerKeepAlivePacket();
		$packet[OutboundPacket::CHUNK_DATA_PACKET][false] = new ChunkDataPacket();
		$packet[OutboundPacket::EFFECT_PACKET][false] = new EffectPacket();
		$packet[OutboundPacket::PARTICLE_PACKET][false] = new ParticlePacket();
		$packet[OutboundPacket::UPDATE_LIGHT_PACKET][false] = new UpdateLightPacket();
		$packet[OutboundPacket::JOIN_GAME_PACKET][false] = new JoinGamePacket();
		$packet[OutboundPacket::MAP_DATA_PACKET][false] = new MapPacket();
		$packet[OutboundPacket::ENTITY_POSITION_PACKET][false] = new MoveEntityPacket();
		$packet[OutboundPacket::ENTITY_ROTATION_PACKET][false] = new EntityRotationPacket();
		$packet[OutboundPacket::OPEN_WINDOW_PACKET][false] = new OpenWindowPacket();
		$packet[OutboundPacket::OPEN_SIGN_EDITOR_PACKET][false] = new OpenSignEditorPacket();
		$packet[OutboundPacket::CRAFT_RECIPE_RESPONSE_PACKET][false] = new CraftRecipeResponsePacket();
		$packet[OutboundPacket::PLAYER_ABILITIES_PACKET][false] = new ServerPlayerAbilitiesPacket();
		$packet[OutboundPacket::PLAYER_INFO_PACKET][false] = new PlayerInfoPacket();
		$packet[OutboundPacket::PLAYER_POSITION_AND_LOOK_PACKET][false] = new PlayerPositionAndLookPacket();
		$packet[OutboundPacket::UNLOCK_RECIPES_PACKET][false] = new UnlockRecipesPacket();
		$packet[OutboundPacket::DESTROY_ENTITIES_PACKET][false] = new DestroyEntitiesPacket();
		$packet[OutboundPacket::REMOVE_ENTITY_EFFECT_PACKET][false] = new RemoveEntityEffectPacket();
		$packet[OutboundPacket::RESPAWN_PACKET][false] = new RespawnPacket();
		$packet[OutboundPacket::ENTITY_HEAD_LOOK_PACKET][false] = new EntityHeadLookPacket();
		$packet[OutboundPacket::SELECT_ADVANCEMENT_TAB_PACKET][false] = new SelectAdvancementTabPacket();
		$packet[OutboundPacket::ACTION_BAR_PACKET][false] = new SetActionBarTextPacket();
		$packet[OutboundPacket::HELD_ITEM_CHANGE_PACKET][false] = new ServerHeldItemChangePacket();
		$packet[OutboundPacket::UPDATE_VIEW_POSITION_PACKET][false] = new UpdateViewPositionPacket();
		$packet[OutboundPacket::UPDATE_VIEW_DISTANCE_PACKET][false] = new UpdateViewDistancePacket();
		$packet[OutboundPacket::SPAWN_POSITION_PACKET][false] = new SpawnPositionPacket();
		$packet[OutboundPacket::DISPLAY_SCOREBOARD_PACKET][false] = new DisplayScoreboardPacket();
		$packet[OutboundPacket::ENTITY_METADATA_PACKET][false] = new EntityMetadataPacket();
		$packet[OutboundPacket::ENTITY_VELOCITY_PACKET][false] = new EntityVelocityPacket();
		$packet[OutboundPacket::ENTITY_EQUIPMENT_PACKET][false] = new EntityEquipmentPacket();
		$packet[OutboundPacket::SET_EXPERIENCE_PACKET][false] = new SetExperiencePacket();
		$packet[OutboundPacket::UPDATE_HEALTH_PACKET][false] = new UpdateHealthPacket();
		$packet[OutboundPacket::SCOREBOARD_OBJECTIVE_PACKET][false] = new ScoreboardObjectivePacket();
		$packet[OutboundPacket::TEAMS_PACKET][false] = new SetPlayerTeamPacket();
		$packet[OutboundPacket::UPDATE_SCORE_PACKET][false] = new UpdateScorePacket();
		$packet[OutboundPacket::SET_TITLE_SUBTITLE_PACKET][false] = new SetSubtitleTextPacket();
		$packet[OutboundPacket::TIME_UPDATE_PACKET][false] = new TimeUpdatePacket();
		$packet[OutboundPacket::SET_TITLE_TEXT_PACKET][false] = new SetTitleTextPacket();
		$packet[OutboundPacket::SET_TITLE_TIME_PACKET][false] = new SetTitlesAnimationPacket();
		$packet[OutboundPacket::SOUND_EFFECT_PACKET][false] = new SoundEffectPacket();
		$packet[OutboundPacket::COLLECT_ITEM_PACKET][false] = new CollectItemPacket();
		$packet[OutboundPacket::ENTITY_TELEPORT_PACKET][false] = new EntityTeleportPacket();
		$packet[OutboundPacket::ADVANCEMENTS_PACKET][false] = new AdvancementsPacket();
		$packet[OutboundPacket::ENTITY_PROPERTIES_PACKET][false] = new EntityPropertiesPacket();
		$packet[OutboundPacket::ENTITY_EFFECT_PACKET][false] = new EntityEffectPacket();*/
		$packetId = [];
		foreach($packet as $pid => $pkdata){
			foreach($pkdata as $type => $pk){
				$packetId[(new ReflectionClass($pk))->getShortName()][$type] = $pid;
			}
		}
		self::$packet = $packet;
		self::$packetId = $packetId;
	}

	public static function getPacket(int $pid, bool $InOrOut): InboundPacket|OutboundPacket|null{
		return self::$packet[$pid][$InOrOut] ?? null;
	}

	public static function getPID(Packet $packet): int|null{
		$InOrOut = ($packet instanceof InboundPacket);
		if(!$InOrOut && !$packet instanceof OutboundPacket){
			return null;
		}
		return self::$packetId[(new ReflectionClass($packet))->getShortName()][$InOrOut];
	}

	/**
	 * @param JavaPlayerNetworkSession $player
	 * @param Packet $packet
	 */
	protected function receivePacket(JavaPlayerNetworkSession $player, Packet $packet)
	{
		$packets = $this->translator->interfaceToServer($player, $packet);
		if ($packets !== null) {
			if (is_array($packets)) {
				foreach ($packets as $packet) {
					$player->handleDataPacket($packet, PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()))->getBuffer());
				}
			} else {
				$player->handleDataPacket($packets, PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()))->getBuffer());
			}
		}
	}

	/**
	 * @param int $identifier
	 */
	public function closeSession(int $identifier)
	{
		if (isset($this->sessionsPlayers[$identifier])) {
			$player = $this->sessionsPlayers[$identifier];
			unset($this->sessionsPlayers[$identifier]);
			$player->disconnect("Connection closed", true);
		}
	}
}
