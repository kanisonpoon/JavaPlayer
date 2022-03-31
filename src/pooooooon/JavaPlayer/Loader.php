<?php

declare(strict_types=1);

namespace pooooooon\javaplayer;

use InvalidArgumentException;
use phpseclib\Crypt\RSA;
use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\player\Player;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pooooooon\javaplayer\info\JavaPlayerInfo;
use pooooooon\javaplayer\listener\JavaPlayerListener;
use pooooooon\javaplayer\network\InfoManager;
use pooooooon\javaplayer\network\JavaPlayerNetworkSession;
use pooooooon\javaplayer\network\protocol\Play\Server\KeepAlivePacket;
use pooooooon\javaplayer\network\ProtocolInterface;
use pooooooon\javaplayer\network\Translator;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;
use ReflectionProperty;

final class Loader extends PluginBase implements Listener
{

	public ProtocolInterface $interface;
	public Translator $translator;
	/** @var array */
	protected $profileCache = [];
	/** @var RSA */
	protected $rsa;
	/** @var JavaPlayerListener[] */
	private array $listeners = [];
	/** @var JavaPlayer[] */
	private array $java_players = [];

	/**
	 * @param string|null $message
	 * @param int $type
	 * @param array|null $parameters
	 * @return string
	 */
	public static function toJSON(?string $message, int $type = 1, ?array $parameters = []): string
	{
		if ($message == null) {
			return "";
		}
		$result = json_decode(self::toJSONInternal($message), true);

		switch ($type) {
			case TextPacket::TYPE_TRANSLATION:
				unset($result["text"]);
				$message = TextFormat::clean($message);

				if (substr($message, 0, 1) === "[") {//chat.type.admin
					$result["translate"] = "chat.type.admin";
					$result["color"] = "gray";
					$result["italic"] = true;
					unset($result["extra"]);

					$result["with"][] = ["text" => substr($message, 1, strpos($message, ":") - 1)];

					if ($message === "[CONSOLE: Reload complete.]" or $message === "[CONSOLE: Reloading server...]") {//blame pmmp
						$result["with"][] = ["translate" => substr(substr($message, strpos($message, ":") + 2), 0, -1), "color" => "yellow"];
					} else {
						$result["with"][] = ["translate" => substr(substr($message, strpos($message, ":") + 2), 0, -1)];
					}

					$with = &$result["with"][1];
				} else {
					$result["translate"] = str_replace("%", "", $message);

					$with = &$result;
				}

				foreach ($parameters as $parameter) {
					if (strpos($parameter, "%") !== false) {
						$with["with"][] = ["translate" => str_replace("%", "", $parameter)];
					} else {
						$with["with"][] = ["text" => $parameter];
					}
				}
				break;
			case TextPacket::TYPE_POPUP:
			case TextPacket::TYPE_TIP://Just to be sure
				if (isset($result["text"])) {
					$result["text"] = str_replace("\n", "", $message);
				}

				if (isset($result["extra"])) {
					unset($result["extra"]);
				}
				break;
		}

		if (isset($result["extra"])) {
			if (count($result["extra"]) === 0) {
				unset($result["extra"]);
			}
		}

		$result = json_encode($result, JSON_UNESCAPED_SLASHES);
		return $result;
	}

	/**
	 * Returns an JSON-formatted string with colors/markup
	 *
	 * @param string|string[] $string
	 * @return string
	 * @internal
	 */
	public static function toJSONInternal($string): string
	{
		if (!is_array($string)) {
			$string = TextFormat::tokenize($string);
		}
		$newString = [];
		$pointer =& $newString;
		$color = "white";
		$bold = false;
		$italic = false;
		$underlined = false;
		$strikethrough = false;
		$obfuscated = false;
		$index = 0;

		foreach ($string as $token) {
			if (isset($pointer["text"])) {
				if (!isset($newString["extra"])) {
					$newString["extra"] = [];
				}
				$newString["extra"][$index] = [];
				$pointer =& $newString["extra"][$index];
				if ($color !== "white") {
					$pointer["color"] = $color;
				}
				if ($bold) {
					$pointer["bold"] = true;
				}
				if ($italic) {
					$pointer["italic"] = true;
				}
				if ($underlined) {
					$pointer["underlined"] = true;
				}
				if ($strikethrough) {
					$pointer["strikethrough"] = true;
				}
				if ($obfuscated) {
					$pointer["obfuscated"] = true;
				}
				++$index;
			}
			switch ($token) {
				case TextFormat::BOLD:
					if (!$bold) {
						$pointer["bold"] = true;
						$bold = true;
					}
					break;
				case TextFormat::OBFUSCATED:
					if (!$obfuscated) {
						$pointer["obfuscated"] = true;
						$obfuscated = true;
					}
					break;
				case TextFormat::ITALIC:
					if (!$italic) {
						$pointer["italic"] = true;
						$italic = true;
					}
					break;
				case TextFormat::UNDERLINE:
					if (!$underlined) {
						$pointer["underlined"] = true;
						$underlined = true;
					}
					break;
				case TextFormat::STRIKETHROUGH:
					if (!$strikethrough) {
						$pointer["strikethrough"] = true;
						$strikethrough = true;
					}
					break;
				case TextFormat::RESET:
					if ($color !== "white") {
						$pointer["color"] = "white";
						$color = "white";
					}
					if ($bold) {
						$pointer["bold"] = false;
						$bold = false;
					}
					if ($italic) {
						$pointer["italic"] = false;
						$italic = false;
					}
					if ($underlined) {
						$pointer["underlined"] = false;
						$underlined = false;
					}
					if ($strikethrough) {
						$pointer["strikethrough"] = false;
						$strikethrough = false;
					}
					if ($obfuscated) {
						$pointer["obfuscated"] = false;
						$obfuscated = false;
					}
					break;

				//Colors
				case TextFormat::BLACK:
					$pointer["color"] = "black";
					$color = "black";
					break;
				case TextFormat::DARK_BLUE:
					$pointer["color"] = "dark_blue";
					$color = "dark_blue";
					break;
				case TextFormat::DARK_GREEN:
					$pointer["color"] = "dark_green";
					$color = "dark_green";
					break;
				case TextFormat::DARK_AQUA:
					$pointer["color"] = "dark_aqua";
					$color = "dark_aqua";
					break;
				case TextFormat::DARK_RED:
					$pointer["color"] = "dark_red";
					$color = "dark_red";
					break;
				case TextFormat::DARK_PURPLE:
					$pointer["color"] = "dark_purple";
					$color = "dark_purple";
					break;
				case TextFormat::GOLD:
					$pointer["color"] = "gold";
					$color = "gold";
					break;
				case TextFormat::GRAY:
					$pointer["color"] = "gray";
					$color = "gray";
					break;
				case TextFormat::DARK_GRAY:
					$pointer["color"] = "dark_gray";
					$color = "dark_gray";
					break;
				case TextFormat::BLUE:
					$pointer["color"] = "blue";
					$color = "blue";
					break;
				case TextFormat::GREEN:
					$pointer["color"] = "green";
					$color = "green";
					break;
				case TextFormat::AQUA:
					$pointer["color"] = "aqua";
					$color = "aqua";
					break;
				case TextFormat::RED:
					$pointer["color"] = "red";
					$color = "red";
					break;
				case TextFormat::LIGHT_PURPLE:
					$pointer["color"] = "light_purple";
					$color = "light_purple";
					break;
				case TextFormat::YELLOW:
					$pointer["color"] = "yellow";
					$color = "yellow";
					break;
				case TextFormat::WHITE:
					$pointer["color"] = "white";
					$color = "white";
					break;
				case TextFormat::MINECOIN_GOLD:
					$pointer["color"] = "yellow";
					$color = "yellow";
					break;
				default:
					$pointer["text"] = $token;
					break;
			}
		}

		if (isset($newString["extra"])) {
			foreach ($newString["extra"] as $k => $d) {
				if (!isset($d["text"])) {
					unset($newString["extra"][$k]);
				}
			}
		}

		$result = json_encode($newString, JSON_UNESCAPED_SLASHES);
		if ($result === false) {
			throw new InvalidArgumentException("Failed to encode result JSON: " . json_last_error_msg());
		}
		return $result;
	}

	public function unregisterListener(JavaPlayerListener $listener): void
	{
		unset($this->listeners[spl_object_id($listener)]);
	}

	public function getJavaPlayer(Player $player): ?JavaPlayer
	{
		return $this->java_players[$player->getUniqueId()->getBytes()] ?? null;
	}

	public function addJavaPlayer($uuid, $xuid, $gamertag, Skin $skin, JavaPlayerNetworkSession $j): void
	{
		$this->addPlayer(new JavaPlayerInfo(Uuid::fromString($uuid), $xuid, $gamertag, $skin, $data["extra_data"] ?? []), $j);
	}

	public function addPlayer(JavaPlayerInfo $info, JavaPlayerNetworkSession $jn): Player
	{
		$server = $this->getServer();
		$network = $server->getNetwork();
		$session = $jn;

		$rp = new ReflectionProperty(NetworkSession::class, "info");
		$rp->setAccessible(true);
		$rp->setValue($session, new XboxLivePlayerInfo($info->xuid, $info->username, $info->uuid, $info->skin, "en_US", $info->extra_data));

		$rp = new ReflectionMethod(NetworkSession::class, "onServerLoginSuccess");
		$rp->setAccessible(true);
		$rp->invoke($session);

		$packet = new ResourcePackClientResponsePacket();
		$packet->status = ResourcePackClientResponsePacket::STATUS_COMPLETED;
		$serializer = PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
		$packet->encode($serializer);
		$session->handleDataPacket($packet, $serializer->getBuffer());

		$session->getPlayer()->setViewDistance(4);

		$pk = new KeepAlivePacket();
		$pk->keepAliveId = mt_rand();
		$jn->putRawPacket($pk);

		$player = $session->getPlayer();
		assert($player !== null);
		$this->java_players[$player->getUniqueId()->getBytes()] = $fake_player = new JavaPlayer($session, $this);

		foreach ($this->listeners as $listener) {
			$listener->onPlayerAdd($player);
		}

		if (!$player->isAlive()) {
			$player->respawn();
		}

		return $player;
	}

	/**
	 * @param PlayerQuitEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event): void
	{
		$player = $event->getPlayer();
		try {
			$this->removePlayer($player, false);
		} catch (InvalidArgumentException $e) {
		}
	}

	public function removePlayer(Player $player, bool $disconnect = true): void
	{
		if (!$this->isJavaPlayer($player)) {
			throw new InvalidArgumentException("Invalid Player supplied, expected a java player, got " . $player->getName());
		}

		if (!isset($this->java_players[$id = $player->getUniqueId()->getBytes()])) {
			return;
		}

		$this->java_players[$id]->destroy();
		unset($this->java_players[$id]);

		if ($disconnect) {
			$player->disconnect("disconnected");
		}

		foreach ($this->listeners as $listener) {
			$listener->onPlayerRemove($player);
		}
	}

	public function isJavaPlayer(Player $player): bool
	{
		return isset($this->java_players[$player->getUniqueId()->getBytes()]);
	}

	/**
	 * @param string $username
	 * @param int $timeout
	 * @return array|null
	 */
	public function getProfileCache(string $username, int $timeout = 60): ?array
	{
		if (isset($this->profileCache[$username]) && (microtime(true) - $this->profileCache[$username]["timestamp"] < $timeout)) {
			return $this->profileCache[$username]["profile"];
		} else {
			unset($this->profileCache[$username]);
			return null;
		}
	}

	/**
	 * @param string $username
	 * @param array $profile
	 */
	public function setProfileCache(string $username, array $profile): void
	{
		$this->profileCache[$username] = [
			"timestamp" => microtime(true),
			"profile" => $profile
		];
	}

	protected function onEnable(): void
	{

		$this->registerListener(new DefaultJavaPlayerListener($this));

		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
			foreach ($this->java_players as $player) {
				$player->tick();
			}
		}), 1);

		$this->saveResource("players.json");

		$ip = (string)$this->getConfig()->get("ip") ?? Server::getInstance()->getIp();
		$port = (int)$this->getConfig()->get("port") ?? 25565;
		$motd = (string)$this->getConfig()->get("motd") ?? Server::getInstance()->getMotd();
		$isUsePmMotd =  (bool)$this->getConfig()->get("UsePmMotd") ?? true;
		if($isUsePmMotd){
			$motd = Server::getInstance()->getMotd();
		}
		$this->translator = new Translator();
		$this->getLogger()->info("Starting Minecraft: PC server on ".($ip === "0.0.0.0" ? "*" : $ip).":".$port." version ".InfoManager::VERSION);
		$this->interface = new ProtocolInterface($this, $this->getServer(), $this->translator, (int)$this->getConfig()->get("network-compression-threshold"), $port, $ip, $motd);
		$this->getServer()->getNetwork()->registerInterface($this->interface);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function registerListener(JavaPlayerListener $listener): void
	{
		$this->listeners[spl_object_id($listener)] = $listener;
		$server = $this->getServer();
		foreach ($this->java_players as $uuid => $_) {
			$listener->onPlayerAdd($server->getPlayerByRawUUID($uuid));
		}
	}
}
