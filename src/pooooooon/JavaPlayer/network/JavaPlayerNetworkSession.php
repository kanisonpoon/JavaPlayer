<?php

declare(strict_types=1);

namespace pooooooon\javaplayer\network;

use Closure;
use pocketmine\block\tile\Spawnable;
use pocketmine\color\Color;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\PacketSender;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\NetworkSessionManager;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Internet;
use pocketmine\world\Position;
use pocketmine\world\World;
use pooooooon\javaplayer\JavaPlayer;
use pooooooon\javaplayer\Loader;
use pooooooon\javaplayer\network\listener\JavaPlayerPacketListener;
use pooooooon\javaplayer\network\listener\JavaPlayerSpecificPacketListener;
use pooooooon\javaplayer\network\protocol\Login\EncryptionResponsePacket;
use pooooooon\javaplayer\network\protocol\Login\LoginSuccessPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\ChangeGameStatePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\ChunkDataPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\CollectItemPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityEquipmentPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\EntityStatusPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\HeldItemChangePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\PlayerAbilitiesPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\PlayerPositionAndLookPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\SpawnPositionPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\TimeUpdatePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\UnloadChunkPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\UpdateLightPacket;
use pooooooon\javaplayer\network\protocol\Play\Server\UpdateViewDistancePacket;
use pooooooon\javaplayer\network\protocol\Play\Server\UpdateViewPositionPacket;
use pooooooon\javaplayer\OldDesktopChunk;
use pooooooon\javaplayer\task\chunktask;
use pooooooon\javaplayer\utils\JavaBinarystream;
use Ramsey\Uuid\Nonstandard\Uuid;
use ReflectionMethod;
use ReflectionProperty;

class JavaPlayerNetworkSession extends NetworkSession
{
	/** @var PromiseResolver */
	private $playerResolver;
	public $status = 0;
	public Loader $loader;
	public string $username = "";
	public string $uuid = "";
	public string $formattedUUID = "";
	public $bigBrother_breakPosition;
	/** @var array */
	protected $bigBrother_properties = [];
	/** @var string[] */
	private $entityList = [];
	/** @var JavaPlayerPacketListener[] */
	private $packet_listeners = [];
	/** @var JavaPlayerSpecificPacketListener|null */
	private $specific_packet_listener;
	public array $clientSetting = [
		"ChatMode" => true,
		"ChatColor" => true,
		"SkinSettings" => 0,
	];
	// private ?JavaInventoryManager $invManager = null;

	public function __construct(Server $server, NetworkSessionManager $manager, PacketPool $packetPool, PacketSender $sender, PacketBroadcaster $broadcaster, Compressor $compressor, string $ip, int $port, Loader $loader)
	{
		parent::__construct($server, $manager, $packetPool, $sender, $broadcaster, $compressor, $ip, $port);
		$this->playerResolver = new PromiseResolver;
		$this->loader = $loader;
		$this->bigBrother_breakPosition = [new Vector3(0, 0, 0), 0];
	}

	public function registerSpecificPacketListener(string $packet, JavaPlayerPacketListener $listener): void
	{
		if ($this->specific_packet_listener === null) {
			$this->specific_packet_listener = new JavaPlayerSpecificPacketListener();
			$this->registerPacketListener($this->specific_packet_listener);
		}
		$this->specific_packet_listener->register($packet, $listener);
	}

	public function registerPacketListener(JavaPlayerPacketListener $listener): void
	{
		$this->packet_listeners[spl_object_id($listener)] = $listener;
	}

	public function unregisterSpecificPacketListener(string $packet, JavaPlayerPacketListener $listener): void
	{
		if ($this->specific_packet_listener !== null) {
			$this->specific_packet_listener->unregister($packet, $listener);
			if ($this->specific_packet_listener->isEmpty()) {
				$this->unregisterPacketListener($this->specific_packet_listener);
				$this->specific_packet_listener = null;
			}
		}
	}

	public function unregisterPacketListener(JavaPlayerPacketListener $listener): void
	{
		unset($this->packet_listeners[spl_object_id($listener)]);
	}

	public function syncAbilities(Player $for) : void{
		$isOp = $for->hasPermission(DefaultPermissions::ROOT_OPERATOR);
		$pk = new PlayerAbilitiesPacket();
		$pk->flyingSpeed = 0.05;
		$pk->viewModifierField = 0.1;
		$pk->canFly = $for->getAllowFlight();
		$pk->damageDisabled = $for->isCreative();
		$pk->isFlying = $for->isFlying();
		$pk->isCreative = $for->isCreative();
		$this->putRawPacket($pk);

		$pk = new EntityStatusPacket();
		$pk->entityStatus = $isOp ? 28 : 24;
		$pk->entityId = $for->getId();
		$this->putRawPacket($pk);
	}

	public function stopUsingChunk(int $chunkX, int $chunkZ): void
	{
		$pk = new UnloadChunkPacket();
		$pk->chunkX = $chunkX;
		$pk->chunkZ = $chunkZ;
		$this->putRawPacket($pk);
	}

	public function putRawPacket(Packet $packet)
	{
		$this->loader->interface->putRawPacket($this, $packet);
	}

	public function putBufferPacket(int $pid, string $buffer)//for test ing
	{
		$this->loader->interface->putBufferPacket($this, $pid, $buffer);
	}

	public function respawn() : void
	{
		$pk = new PlayerPositionAndLookPacket();
		$pk->x = $this->getPlayer()->getPosition()->getX();
		$pk->y = $this->getPlayer()->getPosition()->getY();
		$pk->z = $this->getPlayer()->getPosition()->getZ();
		$pk->yaw = 0;
		$pk->pitch = 0;
		$pk->flags = 0;
		$this->putRawPacket($pk);

		$ch = new \ReflectionProperty($this->getPlayer(), "usedChunks");
		$ch->setAccessible(true);
		$usedChunks = $ch->getValue($this->getPlayer());

		foreach($usedChunks as $index => $d){//reset chunks
			World::getXZ($index, $chunkX, $chunkZ);
			$ref = new \ReflectionMethod($this->getPlayer(), "unloadChunk");
			$ref->setAccessible(true);
			$ref->invoke($this->getPlayer(), $chunkX, $chunkZ);
		}

		$ch->setValue($this->getPlayer(), []);
	}

	public function addToSendBuffer(ClientboundPacket $packet): void
	{
		parent::addToSendBuffer($packet);
		foreach ($this->packet_listeners as $listener) {
			$listener->onPacketSend($packet, $this);
		}
		$packets = $this->loader->translator->serverToInterface($this, $packet);
		if ($packets !== null) {
			/** @var int $target */
			if (is_array($packets)) {
				foreach ($packets as $packet) {
					$this->putRawPacket($packet);
				}
			} else {
				$this->putRawPacket($packets);
			}
		}
	}
	
	public function syncViewAreaRadius(int $distance) : void{
		$pk = new UpdateViewDistancePacket();
		$pk->viewDistance = $distance * 2;
		$this->putRawPacket($pk);
	}
	
	public function syncViewAreaCenterPoint(Vector3 $newPos, int $viewDistance) : void
	{
		$pk = new UpdateViewPositionPacket();
		$pk->chunkX = $newPos->getX() >> 4;
		$pk->chunkZ = $newPos->getZ() >> 4;
		$this->putRawPacket($pk);

		$pk = new UpdateViewDistancePacket();
		$pk->viewDistance = $viewDistance * 2;
		$this->putRawPacket($pk);
	}

	public function syncAvailableCommands() : void{
		$buffer = "";
		$commands = Server::getInstance()->getCommandMap()->getCommands();
		$commandData = [];
		foreach($commands as $name => $command){
			if(isset($commandData[$command->getName()]) || !$command->testPermissionSilent($this->getPlayer())){
				continue;
			}
			$commandData[] = $command;
		}
		$commandCount = count($commandData);
		$buffer .= JavaBinarystream::writeJavaVarInt($commandCount * 2 + 1);
		$buffer .= JavaBinarystream::writeByte(0);
		$buffer .= JavaBinarystream::writeJavaVarInt($commandCount);
		for ($i = 1; $i <= $commandCount * 2; $i++) {
			$buffer .= JavaBinarystream::writeJavaVarInt($i++);
		}
		$i = 1;
		foreach($commandData as $command){
			$buffer .= JavaBinarystream::writeByte(1 | 0x04);
			$buffer .= JavaBinarystream::writeJavaVarInt(1);
			$buffer .= JavaBinarystream::writeJavaVarInt($i + 1);
			$buffer .= JavaBinarystream::writeJavaVarInt(strlen($command->getName())) . $command->getName();
			$i++;
			
			$buffer .= JavaBinarystream::writeByte(2 | 0x04 | 0x10);
			$buffer .= JavaBinarystream::writeJavaVarInt(1);
			$buffer .= JavaBinarystream::writeJavaVarInt($i);
			$buffer .= JavaBinarystream::writeJavaVarInt(strlen("arg")). "arg";
			$buffer .= JavaBinarystream::writeJavaVarInt(strlen("brigadier:string")) . "brigadier:string";
			$buffer .= JavaBinarystream::writeJavaVarInt(0);
			$buffer .= JavaBinarystream::writeJavaVarInt(strlen("minecraft:ask_server")) . "minecraft:ask_server";
			$i++;
		}
		$buffer .= JavaBinarystream::writeJavaVarInt(0);
		$this->putBufferPacket(OutboundPacket::DECLARE_COMMANDS_PACKET, $buffer);
	}

	public function startUsingChunk(int $chunkX, int $chunkZ, Closure $onCompletion): void
	{
		$blockEntities = [];
		foreach($this->getPlayer()->getWorld()->getChunk($chunkX, $chunkZ)->getTiles() as $tile){
			if($tile instanceof Spawnable){
				$blockEntities[] = clone $tile->getSpawnCompound();
			}
		}
		$chunk = new OldDesktopChunk($this, $chunkX, $chunkZ);
		$pk = new UpdateLightPacket();
		$pk->chunkX = $chunkX;
		$pk->chunkZ = $chunkZ;
		$pk->skyLightMask = $chunk->getSkyLightBitMask();
		$pk->blockLightMask = $chunk->getBlockLightBitMask();
		$pk->emptySkyLightMask = ~$chunk->getSkyLightBitMask();
		$pk->emptyBlockLightMask = ~$chunk->getBlockLightBitMask();
		$pk->skyLight = $chunk->getSkyLight();
		$pk->blockLight = $chunk->getBlockLight();
		$this->putRawPacket($pk);

		$pk = new ChunkDataPacket();
		$pk->chunkX = $chunkX;
		$pk->chunkZ = $chunkZ;
		$pk->isFullChunk = $chunk->isFullChunk();
		$pk->primaryBitMask = $chunk->getChunkBitMask();
		// $pk->heightMaps = $chunk->getHeightMaps()->write("");
		$pk->biomes = $chunk->getBiomes();
		$pk->data = $chunk->getChunkData();
		$pk->blockEntities = $blockEntities;
		$this->putRawPacket($pk);
		parent::startUsingChunk($chunkX, $chunkZ, $onCompletion);
	}

	public function bigBrother_getProperties(): array
	{
		return $this->bigBrother_properties;
	}

	public function syncGameMode(GameMode $mode, bool $isRollback = false) : void{
		$val = TypeConverter::getInstance()->coreGameModeToProtocol($mode);
		$pk = new ChangeGameStatePacket();
		$pk->reason = 3;
		if($val == 9){
			$val = 3;
		}
		$pk->value = $val;
		$this->putRawPacket($pk);
		if($this->getPlayer() !== null){
			$this->syncAbilities($this->getPlayer());
		}
	}

	public function syncWorldTime(int $worldTime) : void{
		$pk = new TimeUpdatePacket();
		$pk->worldAge = 0;
		$pk->dayTime = $worldTime;
		$this->putRawPacket($pk);
	}

	public function syncPlayerSpawnPoint(Position $newSpawn) : void{
		$pk = new SpawnPositionPacket();
		$pk->x = $newSpawn->getX();
		$pk->y = $newSpawn->getY();
		$pk->z = $newSpawn->getZ();
		$this->putRawPacket($pk);
	}

	public function syncWorldSpawnPoint(Position $newSpawn) : void{
		$this->syncPlayerSpawnPoint($newSpawn);
	}

	/**
	 * TODO: expand this to more than just humans
	 */
	public function onMobMainHandItemChange(Human $mob) : void{
		$inv = $mob->getInventory();
		if ($mob->getId() === $this->getPlayer()->getId()) {
			$pk = new HeldItemChangePacket();
			$pk->slot = $inv->getHeldItemIndex();
			$this->putRawPacket($pk);
		}
		$pk = new EntityEquipmentPacket();
		$pk->entityId = $mob->getId();
		$pk->slot = 0;//main hand
		$pk->item = $inv->getItemInHand();
		$this->putRawPacket($pk);
	}

	public function onMobOffHandItemChange(Human $mob) : void{
		$inv = $mob->getOffHandInventory();
		$pk = new EntityEquipmentPacket();
		$pk->entityId = $mob->getId();
		$pk->slot = 1;//off hand
		$pk->item = $inv->getItem(0);
		$this->putRawPacket($pk);
	}

	public function onMobArmorChange(Living $mob) : void{
		$inv = $mob->getArmorInventory();
		$slots = [
			2 => $inv->getBoots(),
			3 => $inv->getLeggings(),
			4 => $inv->getChestplate(),
			5 => $inv->getHelmet()
		];
		foreach($slots as $slotid => $item){
			$pk = new EntityEquipmentPacket();
			$pk->entityId = $mob->getId();
			$pk->slot = $slotid;//Armor id
			$pk->item = $item;
			$this->putRawPacket($pk);
		}
	}

	public function onPlayerPickUpItem(Player $collector, Entity $pickedUp) : void{
		$pk = new CollectItemPacket();
		$pk->collectedEntityId = $pickedUp->getId();
		$pk->collectorEntityId = $collector->getId();
		assert($pickedUp instanceof ItemEntity);
		$pk->pickUpItemCount = $pickedUp->getItem()->getCount();
		$this->putRawPacket($pk);
	}

	/**
	 * @param EncryptionResponsePacket $packet
	 */
	public function bigBrother_processAuthentication(EncryptionResponsePacket $packet): void
	{
		$this->bigBrother_secret = $this->loader->decryptBinary($packet->sharedSecret);//todo
		$token = $this->loader->decryptBinary($packet->verifyToken);//todo
		$this->interface->enableEncryption($this, $this->bigBrother_secret);
		if ($token !== $this->bigBrother_checkToken) {
			$this->disconnect("Invalid check token");
		} else {
			$username = $this->bigBrother_username;
			$hash = JavaBinarystream::sha1("" . $this->bigBrother_secret . $this->loader->getASN1PublicKey());

			Server::getInstance()->getAsyncPool()->submitTask(new class($this, $username, $hash) extends AsyncTask {

				/** @var string */
				private $username;
				/** @var string */
				private $hash;

				/**
				 * @param JavaPlayer $player
				 * @param string $username
				 * @param string $hash
				 * @param Closure $onCompletion
				 */
				public function __construct(JavaPlayer $player, string $username, string $hash, Closure $onCompletion)
				{
					self::storeLocal("", $player);
					$this->username = $username;
					$this->hash = $hash;
				}

				/**
				 * @override
				 */
				public function onRun(): void
				{
					$result = null;

					$query = http_build_query([
						"username" => $this->username,
						"serverId" => $this->hash
					]);

					$response = Internet::getURL("https://sessionserver.mojang.com/session/minecraft/hasJoined?" . $query, 5, [], $err);
					if ($response === false || $response->getCode() !== 200) {
						$this->publishProgress("InternetException: failed to fetch session data for '$this->username'; status={$response->getCode()}; err=$err; response_header=" . json_encode($response->getHeaders()));
						$this->setResult(false);
						return;
					}

					$this->setResult(json_decode($response->getBody(), true));
				}

				/**
				 * @override
				 * @param mixed $progress
				 */
				public function onProgressUpdate($progress): void
				{
					Server::getInstance()->getLogger()->error($progress);
				}

				/**
				 * @override
				 */
				public function onCompletion(): void
				{
					$result = $this->getResult();
					/** @var JavaPlayerNetworkSession $player */
					$player = self::fetchLocal("");
					if (is_array($result) and isset($result["id"])) {
						$player->bigBrother_authenticate($result["id"], $result["properties"]);
					} else {
						$player->getPlayer()->kick("User not premium", "User not premium");
					}
				}
			});
		}
	}

	/**
	 * @param string $uuid
	 * @param array|null $onlineModeData
	 */
	public function bigBrother_authenticate(string $uuid, ?array $onlineModeData = null): void
	{
		if ($this->status === 0) {
			$this->uuid = $uuid;
			$this->formattedUUID = Uuid::fromString($this->uuid)->getBytes();

			$this->loader->interface->setCompression($this);

			$pk = new LoginSuccessPacket();

			$pk->uuid = $this->formattedUUID;
			$pk->name = $this->username;

			$this->putRawPacket($pk);

			$this->status = 1;

			if ($onlineModeData !== null) {
				$this->bigBrother_properties = $onlineModeData;
			}

			$model = false;
			$skinImage = "";
			$capeImage = "";
			foreach ($this->bigBrother_properties as $property) {
				if ($property["name"] === "textures") {
					$textures = json_decode(base64_decode($property["value"]), true);

					if (isset($textures["textures"]["SKIN"])) {
						if (isset($textures["textures"]["SKIN"]["metadata"]["model"])) {
							$model = true;
						}

						$skinImage = file_get_contents($textures["textures"]["SKIN"]["url"]);
					} else {
						/*
						 * Detect whether the player has the “Alex?” or “Steve?”
						 * Ref) https://github.com/mapcrafter/mapcrafter-playermarkers/blob/c583dd9157a041a3c9ec5c68244f73b8d01ac37a/playermarkers/player.php#L8-L19
						 */
						if ((bool)(array_reduce(str_split($uuid, 8), function ($acm, $val) {
								return $acm ^ hexdec($val);
							}, 0) % 2)) {
							$skinImage = file_get_contents("http://assets.mojang.com/SkinTemplates/alex.png");
							$model = true;
						} else {
							$skinImage = file_get_contents("http://assets.mojang.com/SkinTemplates/steve.png");
						}
					}

					if (isset($textures["textures"]["CAPE"])) {
						$capeImage = file_get_contents($textures["textures"]["CAPE"]["url"]);
					}
				}
			}
			$SkinId = $this->formattedUUID . "_Custom";
			if ($model) {
				$SkinId .= "Slim";
			}
			$SkinData = (new SkinImage($skinImage))->getSkinImageData(true);
			$CapeData = (new SkinImage($capeImage))->getSkinImageData();
			$skin = new Skin($SkinId, base64_decode($SkinData), base64_decode($CapeData));
			$this->loader->addJavaPlayer($this->uuid, (string)mt_rand(2 * (10 ** 15), (3 * (10 ** 15)) - 1), $this->username, $skin, $this);
		}
	}

	private function getSkinImageSize(int $skinImageLength): array
	{
		return match ($skinImageLength) {
			64 * 32 * 4 => [64, 32],
			64 * 64 * 4 => [64, 64],
			128 * 64 * 4 => [128, 64],
			128 * 128 * 4 => [128, 128],
			default => [0, 0]
		};

	}

	/**
	 * @param int $eid
	 * @param string $entityType
	 */
	public function addEntityList(int $eid, string $entityType): void
	{
		if (!isset($this->entityList[$eid])) {
			$this->entityList[$eid] = $entityType;
		}
	}

	/**
	 * @param int $eid
	 * @return string
	 */
	public function bigBrother_getEntityList(int $eid): string
	{
		if (isset($this->entityList[$eid])) {
			return $this->entityList[$eid];
		}
		return "generic";
	}

	/**
	 * @param int $eid
	 */
	public function removeEntityList(int $eid): void
	{
		if (isset($this->entityList[$eid])) {
			unset($this->entityList[$eid]);
		}
	}

	/**
	 * @param string $username
	 * @param bool $onlineMode
	 */
	public function bigBrother_handleAuthentication(string $username, bool $onlineMode = false): void
	{
		if ($this->status === 0) {
			$this->username = $username;
			/*if($onlineMode){
				$pk = new EncryptionRequestPacket();
				$pk->serverID = "";
				$pk->publicKey = $this->loader->getASN1PublicKey();
				$pk->verifyToken = $this->bigBrother_checkToken = str_repeat("\x00", 4);
				$this->putRawPacket($pk);
			}else*/ {
				if (!is_null(($info = $this->loader->getProfileCache($username)))) {
					//var_dump($info);
					$this->bigBrother_authenticate($info["id"], $info["properties"]);
				} else {
					Server::getInstance()->getAsyncPool()->submitTask(new class($this->loader, $this, $username) extends AsyncTask {

						/** @var string */
						private $username;

						/**
						 * @param Loader $plugin
						 * @param JavaPlayerNetworkSession $player
						 * @param string $username
						 */
						public function __construct(Loader $plugin, JavaPlayerNetworkSession $player, string $username)
						{
							self::storeLocal("", [$plugin, $player]);
							$this->username = $username;
						}

						/**
						 * @override
						 */
						public function onRun(): void
						{
							$profile = null;
							$info = null;

							$response = Internet::getURL("https://api.mojang.com/users/profiles/minecraft/" . $this->username, 10, [], $err);
							var_dump($response);
							if ($response === null) {
								return;
							}
							if ($response->getCode() === 204) {
								//$this->publishProgress("UserNotFound: failed to fetch profile for '$this->username'; status={$response->getCode()}; err=$err; response_header=".json_encode($response->getHeaders()));
								$this->setResult([
									"id" => str_replace("-", "", Uuid::uuid4()->toString()),
									"name" => $this->username,
									"properties" => []
								]);
								return;
							}

							if ($response === false || $response->getCode() !== 200) {
								$this->publishProgress("InternetException: failed to fetch profile for '$this->username'; status={$response->getCode()}; err=$err; response_header=" . json_encode($response->getHeaders()));
								$this->setResult(false);
								return;
							}

							$profile = json_decode($response->getBody(), true);
							if (!is_array($profile)) {
								$this->publishProgress("UnknownError: failed to parse profile for '$this->username'; status={$response->getCode()}; response=$response; response_header=" . json_encode($response->getHeaders()));
								$this->setResult(false);
								return;
							}

							$uuid = $profile["id"];
							$response = Internet::getURL("https://sessionserver.mojang.com/session/minecraft/profile/" . $uuid, 3, [], $err);
							if ($response === false || $response->getCode() !== 200) {
								$this->publishProgress("InternetException: failed to fetch profile info for '$this->username'; status={$response->getCode()}; err=$err; response_header=" . json_encode($response->getHeaders()));
								$this->setResult(false);
								return;
							}

							$info = json_decode($response->getBody(), true);
							if ($info === null or !isset($info["id"])) {
								$this->publishProgress("UnknownError: failed to parse profile info for '$this->username'; status={$response->getCode()}; response=$response; response_header=" . json_encode($response->getHeaders()));
								$this->setResult(false);
								return;
							}

							$this->setResult($info);
						}

						/**
						 * @override
						 * @param mixed $progress
						 */
						public function onProgressUpdate($progress): void
						{
							Server::getInstance()->getLogger()->error($progress);
						}

						/**
						 * @override
						 * @param Server $server
						 */
						public function onCompletion(): void
						{
							$info = $this->getResult();
							if (is_array($info)) {
								list($plugin, $player) = self::fetchLocal("");

								/** @var loader $plugin */
								$plugin->setProfileCache($this->username, $info);

								/** @var JavaPlayerNetworkSession $player */
								$player->bigBrother_authenticate($info["id"], $info["properties"]);
							}
						}
					});
				}
			}
		}
	}
	protected function createPlayer(): void{
		$getProp = function (string $name){
			$rp = new ReflectionProperty(NetworkSession::class, $name);
			$rp->setAccessible(true);
			return $rp->getValue($this);
		};

		$server = $getProp('server');
		$info = $getProp('info');
		$authenticated = $getProp('authenticated');
		$cachedOfflinePlayerData = $getProp('cachedOfflinePlayerData');

		$server->createPlayer($this, $info, $authenticated, $cachedOfflinePlayerData)->onCompletion(
			function (Player $player){
				$rm = new ReflectionMethod(NetworkSession::class, 'onPlayerCreated');
				$rm->setAccessible(true);
				$rm->invoke($this, $player);
				$this->playerResolver->resolve($player);
			},
			fn() => $this->disconnect("Player creation failed")
		);
	}

	public function getPlayerPromise() : Promise{
		return $this->playerResolver->getPromise();
	}

	public function onFailedBlockAction(Vector3 $blockPos, ?int $face) : void{
		if($blockPos->distanceSquared($this->player->getLocation()) < 10000){
			$blocks = $blockPos->sidesArray();
			if($face !== null){
				$sidePos = $blockPos->getSide($face);

				/** @var Vector3[] $blocks */
				array_push($blocks, ...$sidePos->sidesArray()); //getAllSides() on each of these will include $blockPos and $sidePos because they are next to each other
			}else{
				$blocks[] = $blockPos;
			}
			// foreach($this->player->getWorld()->createBlockUpdatePackets(RuntimeBlockMapping::getMappingProtocol($this->session->getProtocolId()), $blocks) as $packet){
			// 	$this->session->sendDataPacket($packet);
			// }
		}
	}
}

class SkinImage
{
	private $utils, $existSkinImage = false;

	public function __construct($binary)
	{
		$this->utils = new PNGParser($binary);
		if ($binary !== "") {
			$this->existSkinImage = true;
		}
	}

	public function getSkinImageData(bool $enableDummyImage = false): string
	{
		return base64_encode($this->getRawSkinImageData($enableDummyImage));
	}

	public function getRawSkinImageData(bool $enableDummyImage = false): string
	{
		$data = "";
		if ($this->existSkinImage) {
			for ($height = 0; $height < $this->utils->getHeight(); $height++) {
				for ($width = 0; $width < $this->utils->getWidth(); $width++) {
					$rgbaData = $this->utils->getRGBA($height, $width);
					$data .= chr($rgbaData[0]) . chr($rgbaData[1]) . chr($rgbaData[2]) . chr($rgbaData[3]);
				}
			}
		} elseif ($enableDummyImage) {
			$data = str_repeat(" ", 64 * 32 * 4);//dummy data
		}

		return $data;
	}

}


class PNGParser
{
	const PNGFileSignature = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";

	private $stream;
	private $width = 0, $height = 0;
	private $isPalette = false, $palette = [];
	private $bitDepth = 8, $colorType = 6, $isAlpha = true;
	private $compressionMethod = 0, $filterMethod = 0, $interlaceMethod = 0;
	private $pixelData = [[[0, 0, 0, 255]]];
	private $rawImageData = "";
	private $usedBit = 0, $usedBitNum = 0;

	public function __construct($binary = "")
	{
		$this->stream = new BinaryStream($binary);
		if ($binary !== "") {
			$this->read();
		}
	}

	private function read()
	{
		if ($this->stream->get(8) !== self::PNGFileSignature) {
			echo "Error\n";
			return;
		}

		while (!$this->stream->feof()) {
			$length = $this->stream->getInt();
			$chunkType = $this->stream->get(4);

			switch ($chunkType) {
				case "IHDR":
					$this->readIHDR();
					break;
				case "PLTE":
					$this->readPLTE($length);
					break;
				case "IDAT":
					$this->readIDAT($length);
					break;
				case "IEND":
					$this->readIEND($length);
					break;
				case "tRNS":
					$this->readtRNS($length);
					break;
				default:
					$this->stream->setOffset($this->stream->getOffset() + $length);
					break;
			}

			$this->stream->getInt();//crc32
		}

		$this->readAllIDAT();
	}

	private function readIHDR()
	{
		$this->setWidth($this->stream->getInt());
		$this->setHeight($this->stream->getInt());
		$this->bitDepth = $this->stream->getByte();
		$this->colorType = $this->stream->getByte();
		$this->compressionMethod = $this->stream->getByte();
		$this->filterMethod = $this->stream->getByte();
		$this->interlaceMethod = $this->stream->getByte();

		if ($this->colorType === 3) {
			$this->isPalette = true;
		}

		if ($this->colorType === 4 or $this->colorType === 6) {
			$this->isAlpha = true;
		} else {
			$this->isAlpha = false;
		}
	}

	public function setHeight(int $height)
	{
		$this->height = $height;
		$this->generatePixelData();
	}

	private function generatePixelData()
	{
		$old_pixelData = $this->pixelData;
		$this->pixelData = [];

		for ($height = 0; $height < $this->height; $height++) {
			$this->pixelData[$height] = [];

			for ($width = 0; $width < $this->width; $width++) {
				$pixel = [0, 0, 0, 255];
				if (isset($old_pixelData[$height][$width])) {
					$pixel = $old_pixelData[$height][$width];
				}

				$this->pixelData[$height][$width] = $pixel;
			}
		}
	}

	private function readPLTE(int $length)
	{
		$this->isPalette = true;//unused?

		$paletteCount = $length / 3;
		for ($i = 0; $i < $paletteCount; $i++) {
			$r = $this->stream->getByte();
			$g = $this->stream->getByte();
			$b = $this->stream->getByte();
			$a = 255;
			$this->palette[] = [$r, $g, $b, $a];
		}
	}

	private function readIDAT(int $length)
	{
		$chunkData = zlib_decode($this->stream->get($length));

		$this->rawImageData .= $chunkData;
	}

	private function readIEND($length)
	{
		//No chunk data
	}

	private function readtRNS(int $length)
	{
		switch ($this->colorType) {
			/*case 0:

			break;
			case 2:

			break;*/
			case 3:
				for ($i = 0; $i < $length; $i++) {
					$this->palette[$i][3] = $this->stream->getByte();
				}
				break;
			default:
				echo "Sorry, i can't parse png file. readtRNS: " . $this->colorType . "\n";
				echo "Report to BigBrotherTeam!\n";
				break;
		}
	}

	private function readAllIDAT()
	{
		$stream = new BinaryStream($this->rawImageData);

		for ($height = 0; $height < $this->height; $height++) {
			$filterMethod = $stream->getByte();

			for ($width = 0; $width < $this->width; $width++) {
				if ($this->isPalette) {
					$paletteIndex = $this->getData($stream);
					$rgb = $this->palette[$paletteIndex];

					$this->setRGBA($height, $width, [$rgb[0], $rgb[1], $rgb[2], $rgb[3]]);
				} else {
					$r = $this->getData($stream);
					$g = $this->getData($stream);
					$b = $this->getData($stream);
					if ($this->isAlpha) {
						$a = $this->getData($stream);
					} else {
						$a = 255;
					}

					switch ($filterMethod) {
						case 0://None
							break;
						case 1://Sub
							$left = $this->getRGBA($height, $width - 1);
							$r = $this->calculateColor($r, $left[0]);
							$g = $this->calculateColor($g, $left[1]);
							$b = $this->calculateColor($b, $left[2]);
							$a = $this->calculateColor($a, $left[3]);
							break;
						case 2://Up
							$above = $this->getRGBA($height - 1, $width);
							$r = $this->calculateColor($r, $above[0]);
							$g = $this->calculateColor($g, $above[1]);
							$b = $this->calculateColor($b, $above[2]);
							$a = $this->calculateColor($a, $above[3]);
							break;
						case 3://Average
							$left = $this->getRGBA($height, $width - 1);
							$above = $this->getRGBA($height - 1, $width);
							$avrgR = $this->average($left[0], $above[0]);
							$avrgG = $this->average($left[1], $above[1]);
							$avrgB = $this->average($left[2], $above[2]);
							$avrgA = $this->average($left[3], $above[3]);

							$r = $this->calculateColor($r, $avrgR);
							$g = $this->calculateColor($g, $avrgG);
							$b = $this->calculateColor($b, $avrgB);
							$a = $this->calculateColor($a, $avrgA);
							break;
						case 4://Paeth
							$left = $this->getRGBA($height, $width - 1);
							$above = $this->getRGBA($height - 1, $width);
							$upperLeft = $this->getRGBA($height - 1, $width - 1);

							$paethR = $this->paethPredictor($left[0], $above[0], $upperLeft[0]);
							$paethG = $this->paethPredictor($left[1], $above[1], $upperLeft[1]);
							$paethB = $this->paethPredictor($left[2], $above[2], $upperLeft[2]);
							$paethA = $this->paethPredictor($left[3], $above[3], $upperLeft[3]);

							$r = $this->calculateColor($r, $paethR);
							$g = $this->calculateColor($g, $paethG);
							$b = $this->calculateColor($b, $paethB);
							$a = $this->calculateColor($a, $paethA);
							break;
					}

					$this->setRGBA($height, $width, [$r, $g, $b, $a]);
				}
			}
		}
	}

	private function getData(BinaryStream $stream): int
	{
		switch ($this->bitDepth) {
			/*case 1:

			break;
			case 2:

			break;*/
			case 4:
				if ($this->usedBitNum === 0) {
					$this->usedBit = $stream->getByte();
					$this->usedBitNum = 4;

					return $this->usedBit >> 4;
				} else {
					$this->usedBitNum = 0;

					return $this->usedBit & 0x0f;
				}
			case 8:
				return $stream->getByte();
			case 16:
				return $stream->getShort();
			default:
				echo "Sorry, i can't parse png file. getData: " . $this->bitDepth . "\n";
				echo "Report to BigBrotherTeam!\n";
				break;
		}

		return 0;
	}

	public function setRGBA(int $x, int $z, array $pixelData): bool
	{
		if (isset($this->pixelData[$x][$z])) {
			$this->pixelData[$x][$z] = $pixelData;
			return true;
		}

		return false;
	}

	public function getRGBA($x, $z): array
	{
		if (isset($this->pixelData[$x][$z])) {
			return $this->pixelData[$x][$z];
		}

		return [0, 0, 0, 0];//Don't change it.
	}

	private function calculateColor($color1, $color2): int
	{
		return ($color1 + $color2) % 256;
	}

	private function average($color1, $color2)
	{
		return floor(($color1[0] + $color2[0]) / 2);
	}

	private function paethPredictor($a, $b, $c)
	{
		$p = $a + $b - $c;
		$pa = abs($p - $a);
		$pb = abs($p - $b);
		$pc = abs($p - $c);
		if ($pa <= $pb && $pa <= $pc) {
			return $a;
		} elseif ($pb <= $pc) {
			return $b;
		} else {
			return $c;
		}
	}

	public function getWidth(): int
	{
		return $this->width;
	}

	public function setWidth(int $width)
	{
		$this->width = $width;
		$this->generatePixelData();
	}

	public function getHeight(): int
	{
		return $this->height;
	}

	public function getBinary(): string
	{
		return $this->stream->getBuffer();
	}

	//TODO: write image data

}

class ColorUtils
{

	// TODO this color table is not up-to-date (please update me!!)
	/** @var array */
	private static $colorTable = [
		0x04 => [0x59, 0x7D, 0x27], // Grass
		0x05 => [0x6D, 0x99, 0x30], // Grass
		0x06 => [0x7F, 0xB2, 0x38], // Grass
		0x07 => [0x6D, 0x99, 0x30], // Grass
		0x08 => [0xAE, 0xA4, 0x73], // Sand
		0x09 => [0xD5, 0xC9, 0x8C], // Sand
		0x0A => [0xF7, 0xE9, 0xA3], // Sand
		0x0B => [0xD5, 0xC9, 0x8C], // Sand
		0x0C => [0x75, 0x75, 0x75], // Cloth
		0x0D => [0x90, 0x90, 0x90], // Cloth
		0x0E => [0xA7, 0xA7, 0xA7], // Cloth
		0x0F => [0x90, 0x90, 0x90], // Cloth
		0x10 => [0xB4, 0x00, 0x00], // Fire
		0x11 => [0xDC, 0x00, 0x00], // Fire
		0x12 => [0xFF, 0x00, 0x00], // Fire
		0x13 => [0xDC, 0x00, 0x00], // Fire
		0x14 => [0x70, 0x70, 0xB4], // Ice
		0x15 => [0x8A, 0x8A, 0xDC], // Ice
		0x16 => [0xA0, 0xA0, 0xFF], // Ice
		0x17 => [0x8A, 0x8A, 0xDC], // Ice
		0x18 => [0x75, 0x75, 0x75], // Iron
		0x19 => [0x90, 0x90, 0x90], // Iron
		0x1A => [0xA7, 0xA7, 0xA7], // Iron
		0x1B => [0x90, 0x90, 0x90], // Iron
		0x1C => [0x00, 0x57, 0x00], // Foliage
		0x1D => [0x00, 0x6A, 0x00], // Foliage
		0x1E => [0x00, 0x7C, 0x00], // Foliage
		0x1F => [0x00, 0x6A, 0x00], // Foliage
		0x20 => [0xB4, 0xB4, 0xB4], // Snow
		0x21 => [0xDC, 0xDC, 0xDC], // Snow
		0x22 => [0xFF, 0xFF, 0xFF], // Snow
		0x23 => [0xDC, 0xDC, 0xDC], // Snow
		0x24 => [0x73, 0x76, 0x81], // Clay
		0x25 => [0x8D, 0x90, 0x9E], // Clay
		0x26 => [0xA4, 0xA8, 0xB8], // Clay
		0x27 => [0x8D, 0x90, 0x9E], // Clay
		0x28 => [0x81, 0x4A, 0x21], // Dirt
		0x29 => [0x9D, 0x5B, 0x28], // Dirt
		0x2A => [0xB7, 0x6A, 0x2F], // Dirt
		0x2B => [0x9D, 0x5B, 0x28], // Dirt
		0x2C => [0x4F, 0x4F, 0x4F], // Stone
		0x2D => [0x60, 0x60, 0x60], // Stone
		0x2E => [0x70, 0x70, 0x70], // Stone
		0x2F => [0x60, 0x60, 0x60], // Stone
		0x30 => [0x2D, 0x2D, 0xB4], // Water
		0x31 => [0x37, 0x37, 0xDC], // Water
		0x32 => [0x40, 0x40, 0xFF], // Water
		0x33 => [0x37, 0x37, 0xDC], // Water
		0x34 => [0x49, 0x3A, 0x23], // Wood
		0x35 => [0x59, 0x47, 0x2B], // Wood
		0x36 => [0x68, 0x53, 0x32], // Wood
		0x37 => [0x59, 0x47, 0x2B], // Wood
		0x38 => [0xB4, 0xB1, 0xAC], // Quartz, Sea Lantern, Birch Log
		0x39 => [0xDC, 0xD9, 0xD3], // Quartz, Sea Lantern, Birch Log
		0x3A => [0xFF, 0xFC, 0xF5], // Quartz, Sea Lantern, Birch Log
		0x3B => [0x87, 0x85, 0x81], // Quartz, Sea Lantern, Birch Log
		0x3C => [0x98, 0x59, 0x24], // Orange Wool/Glass/Stained Clay, Pumpkin, Hardened Clay, Acacia Plank
		0x3D => [0xBA, 0x6D, 0x2C], // Orange Wool/Glass/Stained Clay, Pumpkin, Hardened Clay, Acacia Plank
		0x3E => [0xD8, 0x7F, 0x33], // Orange Wool/Glass/Stained Clay, Pumpkin, Hardened Clay, Acacia Plank
		0x3F => [0x72, 0x43, 0x1B], // Orange Wool/Glass/Stained Clay, Pumpkin, Hardened Clay, Acacia Plank
		0x40 => [0x7D, 0x35, 0x98], // Magenta Wool/Glass/Stained Clay
		0x41 => [0x99, 0x41, 0xBA], // Magenta Wool/Glass/Stained Clay
		0x42 => [0xB2, 0x4C, 0xD8], // Magenta Wool/Glass/Stained Clay
		0x43 => [0x5E, 0x28, 0x72], // Magenta Wool/Glass/Stained Clay
		0x44 => [0x48, 0x6C, 0x98], // Light Blue Wool/Glass/Stained Clay
		0x45 => [0x58, 0x84, 0xBA], // Light Blue Wool/Glass/Stained Clay
		0x46 => [0x66, 0x99, 0xD8], // Light Blue Wool/Glass/Stained Clay
		0x47 => [0x36, 0x51, 0x72], // Light Blue Wool/Glass/Stained Clay
		0x48 => [0xA1, 0xA1, 0x24], // Yellow Wool/Glass/Stained Clay, Sponge, Hay Bale
		0x49 => [0xC5, 0xC5, 0x2C], // Yellow Wool/Glass/Stained Clay, Sponge, Hay Bale
		0x4A => [0xE5, 0xE5, 0x33], // Yellow Wool/Glass/Stained Clay, Sponge, Hay Bale
		0x4B => [0x79, 0x79, 0x1B], // Yellow Wool/Glass/Stained Clay, Sponge, Hay Bale
		0x4C => [0x59, 0x90, 0x11], // Lime Wool/Glass/Stained Clay, Melon
		0x4D => [0x6D, 0xB0, 0x15], // Lime Wool/Glass/Stained Clay, Melon
		0x4E => [0x7F, 0xCC, 0x19], // Lime Wool/Glass/Stained Clay, Melon
		0x4F => [0x43, 0x6C, 0x0D], // Lime Wool/Glass/Stained Clay, Melon
		0x50 => [0xAA, 0x59, 0x74], // Pink Wool/Glass/Stained Clay
		0x51 => [0xD0, 0x6D, 0x8E], // Pink Wool/Glass/Stained Clay
		0x52 => [0xF2, 0x7F, 0xA5], // Pink Wool/Glass/Stained Clay
		0x53 => [0x80, 0x43, 0x57], // Pink Wool/Glass/Stained Clay
		0x54 => [0x35, 0x35, 0x35], // Grey Wool/Glass/Stained Clay
		0x55 => [0x41, 0x41, 0x41], // Grey Wool/Glass/Stained Clay
		0x56 => [0x4C, 0x4C, 0x4C], // Grey Wool/Glass/Stained Clay
		0x57 => [0x28, 0x28, 0x28], // Grey Wool/Glass/Stained Clay
		0x58 => [0x6C, 0x6C, 0x6C], // Light Grey Wool/Glass/Stained Clay
		0x59 => [0x84, 0x84, 0x84], // Light Grey Wool/Glass/Stained Clay
		0x5A => [0x99, 0x99, 0x99], // Light Grey Wool/Glass/Stained Clay
		0x5B => [0x51, 0x51, 0x51], // Light Grey Wool/Glass/Stained Clay
		0x5C => [0x35, 0x59, 0x6C], // Cyan Wool/Glass/Stained Clay
		0x5D => [0x41, 0x6D, 0x84], // Cyan Wool/Glass/Stained Clay
		0x5E => [0x4C, 0x7F, 0x99], // Cyan Wool/Glass/Stained Clay
		0x5F => [0x28, 0x43, 0x51], // Cyan Wool/Glass/Stained Clay
		0x60 => [0x59, 0x2C, 0x7D], // Purple Wool/Glass/Stained Clay, Mycelium
		0x61 => [0x6D, 0x36, 0x99], // Purple Wool/Glass/Stained Clay, Mycelium
		0x62 => [0x7F, 0x3F, 0xB2], // Purple Wool/Glass/Stained Clay, Mycelium
		0x63 => [0x43, 0x21, 0x5E], // Purple Wool/Glass/Stained Clay, Mycelium
		0x64 => [0x24, 0x35, 0x7D], // Blue Wool/Glass/Stained Clay
		0x65 => [0x2C, 0x41, 0x99], // Blue Wool/Glass/Stained Clay
		0x66 => [0x33, 0x4C, 0xB2], // Blue Wool/Glass/Stained Clay
		0x67 => [0x1B, 0x28, 0x5E], // Blue Wool/Glass/Stained Clay
		0x68 => [0x48, 0x35, 0x24], // Brown Wool/Glass/Stained Clay, Soul Sand, Dark Oak Plank
		0x69 => [0x58, 0x41, 0x2C], // Brown Wool/Glass/Stained Clay, Soul Sand, Dark Oak Plank
		0x6A => [0x66, 0x4C, 0x33], // Brown Wool/Glass/Stained Clay, Soul Sand, Dark Oak Plank
		0x6B => [0x36, 0x28, 0x1B], // Brown Wool/Glass/Stained Clay, Soul Sand, Dark Oak Plank
		0x6C => [0x48, 0x59, 0x24], // Green Wool/Glass/Stained Clay, End Portal Frame
		0x6D => [0x58, 0x6D, 0x2C], // Green Wool/Glass/Stained Clay, End Portal Frame
		0x6E => [0x66, 0x7F, 0x33], // Green Wool/Glass/Stained Clay, End Portal Frame
		0x6F => [0x36, 0x43, 0x1B], // Green Wool/Glass/Stained Clay, End Portal Frame
		0x70 => [0x6C, 0x24, 0x24], // Red Wool/Glass/Stained Clay, Huge Red Mushroom, Brick, Enchanting Table
		0x71 => [0x84, 0x2C, 0x2C], // Red Wool/Glass/Stained Clay, Huge Red Mushroom, Brick, Enchanting Table
		0x72 => [0x99, 0x33, 0x33], // Red Wool/Glass/Stained Clay, Huge Red Mushroom, Brick, Enchanting Table
		0x73 => [0x51, 0x1B, 0x1B], // Red Wool/Glass/Stained Clay, Huge Red Mushroom, Brick, Enchanting Table
		0x74 => [0x11, 0x11, 0x11], // Black Wool/Glass/Stained Clay, Dragon Egg, Block of Coal, Obsidian
		0x75 => [0x15, 0x15, 0x15], // Black Wool/Glass/Stained Clay, Dragon Egg, Block of Coal, Obsidian
		0x76 => [0x19, 0x19, 0x19], // Black Wool/Glass/Stained Clay, Dragon Egg, Block of Coal, Obsidian
		0x77 => [0x0D, 0x0D, 0x0D], // Black Wool/Glass/Stained Clay, Dragon Egg, Block of Coal, Obsidian
		0x78 => [0xB0, 0xA8, 0x36], // Block of Gold, Weighted Pressure Plate (Light)
		0x79 => [0xD7, 0xCD, 0x42], // Block of Gold, Weighted Pressure Plate (Light)
		0x7A => [0xFA, 0xEE, 0x4D], // Block of Gold, Weighted Pressure Plate (Light)
		0x7B => [0x84, 0x7E, 0x28], // Block of Gold, Weighted Pressure Plate (Light)
		0x7C => [0x40, 0x9A, 0x96], // Block of Diamond, Prismarine, Prismarine Bricks, Dark Prismarine, Beacon
		0x7D => [0x4F, 0xBC, 0xB7], // Block of Diamond, Prismarine, Prismarine Bricks, Dark Prismarine, Beacon
		0x7E => [0x5C, 0xDB, 0xD5], // Block of Diamond, Prismarine, Prismarine Bricks, Dark Prismarine, Beacon
		0x7F => [0x30, 0x73, 0x70], // Block of Diamond, Prismarine, Prismarine Bricks, Dark Prismarine, Beacon
		0x80 => [0x34, 0x5A, 0xB4], // Lapis Lazuli Block
		0x81 => [0x3F, 0x6E, 0xDC], // Lapis Lazuli Block
		0x82 => [0x4A, 0x80, 0xFF], // Lapis Lazuli Block
		0x83 => [0x27, 0x43, 0x87], // Lapis Lazuli Block
		0x84 => [0x00, 0x99, 0x28], // Block of Emerald
		0x85 => [0x00, 0xBB, 0x32], // Block of Emerald
		0x86 => [0x00, 0xD9, 0x3A], // Block of Emerald
		0x87 => [0x00, 0x72, 0x1E], // Block of Emerald
		0x88 => [0x5A, 0x3B, 0x22], // Podzol, Spruce Plank
		0x89 => [0x6E, 0x49, 0x29], // Podzol, Spruce Plank
		0x8A => [0x7F, 0x55, 0x30], // Podzol, Spruce Plank
		0x8B => [0x43, 0x2C, 0x19], // Podzol, Spruce Plank
		0x8C => [0x4F, 0x01, 0x00], // Netherrack, Quartz Ore, Nether Wart, Nether Brick Items
		0x8D => [0x60, 0x01, 0x00], // Netherrack, Quartz Ore, Nether Wart, Nether Brick Items
		0x8E => [0x70, 0x02, 0x00], // Netherrack, Quartz Ore, Nether Wart, Nether Brick Items
		0x8F => [0x3B, 0x01, 0x00], // Netherrack, Quartz Ore, Nether Wart, Nether Brick Items
	];

	/** @var ?string */
	private static $index = null;

	/** @var int */
	protected $a;
	/** @var int */
	protected $r;
	/** @var int */
	protected $g;
	/** @var int */
	protected $b;

	public function __construct(int $r, int $g, int $b, int $a = 0xff)
	{
		$this->r = $r & 0xff;
		$this->g = $g & 0xff;
		$this->b = $b & 0xff;
		$this->a = $a & 0xff;
	}

	/**
	 * Mixes the supplied list of colours together to produce a result colour.
	 *
	 * @param ColorUtils $color1
	 * @param Color ...$colors
	 * @return Color
	 */
	public static function mix(ColorUtils $color1, Color ...$colors): Color
	{
		$colors[] = $color1;
		$count = count($colors);

		$a = $r = $g = $b = 0;

		foreach ($colors as $color) {
			$a += $color->a;
			$r += $color->r;
			$g += $color->g;
			$b += $color->b;
		}

		return new Color(intdiv($r, $count), intdiv($g, $count), intdiv($b, $count), intdiv($a, $count));
	}

	/**
	 * Returns a Color from the supplied RGB colour code (24-bit)
	 */
	public static function fromRGB(int $code): Color
	{
		return new Color(($code >> 16) & 0xff, ($code >> 8) & 0xff, $code & 0xff);
	}

	/**
	 * Returns a Color from the supplied ARGB colour code (32-bit)
	 */
	public static function fromARGB(int $code): Color
	{
		return new Color(($code >> 16) & 0xff, ($code >> 8) & 0xff, $code & 0xff, ($code >> 24) & 0xff);
	}

	/**
	 * Returns a Color from the supplied RGBA colour code (32-bit)
	 */
	public static function fromRGBA(int $c): Color
	{
		return new Color(($c >> 24) & 0xff, ($c >> 16) & 0xff, ($c >> 8) & 0xff, $c & 0xff);
	}

	/**
	 * @var string $path
	 * @internal
	 */
	public static function generateColorIndex(string $path)
	{
		$indexes = "";

		for ($r = 0; $r < 256; ++$r) {
			for ($g = 0; $g < 256; ++$g) {
				for ($b = 0; $b < 256; ++$b) {
					$ind = 0x00;
					$min = PHP_INT_MAX;

					foreach (self::$colorTable as $index => $rgb) {
						$squared = ($rgb[0] - $r) ** 2 + ($rgb[1] - $g) ** 2 + ($rgb[2] - $b) ** 2;
						if ($squared < $min) {
							$ind = $index;
							$min = $squared;
						}
					}

					$indexes .= chr($ind);
				}
			}
		}

		file_put_contents($path, zlib_encode($indexes, ZLIB_ENCODING_DEFLATE, 9));
	}

	/**
	 * @var string $path
	 */
	public static function loadColorIndex(string $path)
	{
		self::$index = zlib_decode(file_get_contents($path));
	}

	/**
	 * Find nearest color defined in self::$colorTable for each pixel in $colors
	 *
	 * @param ColorUtils[][] $colors
	 * @param int $width
	 * @param int $height
	 * @return string
	 */
	public static function convertColorsToPC(array $colors, int $width, int $height): string
	{
		$ret = "";

		for ($y = 0; $y < $height; ++$y) {
			for ($x = 0; $x < $width; ++$x) {
				$ret .= $colors[$y][$x]->a >= 128 ? self::$index[($colors[$y][$x]->r << 16) + ($colors[$y][$x]->g << 8) + $colors[$y][$x]->b] : chr(0x00);
			}
		}

		return $ret;
	}

	/**
	 * Returns the alpha (opacity) value of this colour.
	 */
	public function getA(): int
	{
		return $this->a;
	}

	/**
	 * Retuns the red value of this colour.
	 */
	public function getR(): int
	{
		return $this->r;
	}

	/**
	 * Returns the green value of this colour.
	 */
	public function getG(): int
	{
		return $this->g;
	}

	/**
	 * Returns the blue value of this colour.
	 */
	public function getB(): int
	{
		return $this->b;
	}

	/**
	 * Returns an ARGB 32-bit colour value.
	 */
	public function toARGB(): int
	{
		return ($this->a << 24) | ($this->r << 16) | ($this->g << 8) | $this->b;
	}

	/**
	 * Returns an RGBA 32-bit colour value.
	 */
	public function toRGBA(): int
	{
		return ($this->r << 24) | ($this->g << 16) | ($this->b << 8) | $this->a;
	}
}
