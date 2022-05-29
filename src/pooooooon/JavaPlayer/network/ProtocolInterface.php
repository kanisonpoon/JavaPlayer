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
		// $this->PacketList();
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
			switch ($pid) {
				case InboundPacket::TELEPORT_CONFIRM_PACKET:
					$pk = new TeleportConfirmPacket();
					break;
				case InboundPacket::CHAT_MESSAGE_PACKET:
					$pk = new ChatMessagePacket();
					break;
				case InboundPacket::CLIENT_STATUS_PACKET:
					$pk = new ClientStatusPacket();
					break;
				case InboundPacket::CLIENT_SETTINGS_PACKET:
					$pk = new ClientSettingsPacket();
					break;
				case InboundPacket::TAB_COMPLETE_PACKET:
					$pk = new TabCompletePacket();
					break;
				case InboundPacket::WINDOW_CONFIRMATION_PACKET:
					$pk = new WindowConfirmationPacket();
					break;
				case InboundPacket::CLICK_WINDOW_PACKET:
					$pk = new ClickWindowPacket();
					break;
				case InboundPacket::CLOSE_WINDOW_PACKET:
					$pk = new CloseWindowPacket();
					break;
				case InboundPacket::PLUGIN_MESSAGE_PACKET:
					$pk = new PluginMessagePacket();
					break;
				case InboundPacket::INTERACT_ENTITY_PACKET:
					$pk = new InteractEntityPacket();
					break;
				case InboundPacket::KEEP_ALIVE_PACKET:
					$pk = new KeepAlivePacket();
					break;
				case InboundPacket::PLAYER_POSITION_PACKET:
					$pk = new PlayerPositionPacket();
					break;
				case InboundPacket::PLAYER_POSITION_AND_ROTATION_PACKET:
					$pk = new PlayerPositionAndRotationPacket();
					break;
				case InboundPacket::PLAYER_ROTATION_PACKET:
					$pk = new PlayerRotationPacket();
					break;
				case InboundPacket::PLAYER_MOVEMENT_PACKET:
					$pk = new PlayerMovementPacket();
					break;
				case InboundPacket::CRAFT_RECIPE_REQUEST_PACKET:
					$pk = new CraftRecipeRequestPacket();
					break;
				case InboundPacket::PLAYER_ABILITIES_PACKET:
					$pk = new PlayerAbilitiesPacket();
					break;
				case InboundPacket::PLAYER_DIGGING_PACKET:
					$pk = new PlayerDiggingPacket();
					break;
				case InboundPacket::ENTITY_ACTION_PACKET:
					$pk = new EntityActionPacket();
					break;
				case InboundPacket::ADVANCEMENT_TAB_PACKET:
					$pk = new AdvancementTabPacket();
					break;
				case InboundPacket::HELD_ITEM_CHANGE_PACKET:
					$pk = new HeldItemChangePacket();
					break;
				case InboundPacket::CREATIVE_INVENTORY_ACTION_PACKET:
					$pk = new CreativeInventoryActionPacket();
					break;
				case InboundPacket::UPDATE_SIGN_PACKET:
					$pk = new UpdateSignPacket();
					break;
				case InboundPacket::ANIMATION_PACKET:
					$pk = new AnimationPacket();
					break;
				case InboundPacket::PLAYER_BLOCK_PLACEMENT_PACKET:
					$pk = new PlayerBlockPlacementPacket();
					break;
				case InboundPacket::USE_ITEM_PACKET:
					$pk = new UseItemPacket();
					break;
				default:
					//if(DEBUG > 4){
					echo "[Receive][Interface] 0x" . bin2hex(chr($pid)) . " Not implemented\n"; //Debug
					//}
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
		//TODO:add ALL PACKET
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
		return self::$packet[$pid][$InOrOut];
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
