<?php

declare(strict_types=1);

namespace pooooooon\javaplayer\network;

use pooooooon\javaplayer\Loader;
use pooooooon\javaplayer\network\protocol\Login\LoginDisconnectPacket;
use pooooooon\javaplayer\network\protocol\Status\PingPacket;
use pooooooon\javaplayer\utils\JavaBinarystream;
use Crypto\Cipher;

class Session
{
	/** @var string */
	protected $address;
	/** @var int */
	protected $port;
	/** @var bool */
	protected $encryptionEnabled = false;
	/** @var InfoManager */
	private $manager;
	/** @var int */
	private $identifier;
	/** @var resource */
	private $socket;
	/** @var int */
	private $status = 0;
	/** @var ?int */
	private $threshold = null;
	private Cipher $decryptCipher;
	private Cipher $encryptCipher;

	/**
	 * @param InfoManager $manager
	 * @param int $identifier
	 * @param resource $socket
	 */
	public function __construct(InfoManager $manager, int $identifier, $socket)
	{
		$this->manager = $manager;
		$this->identifier = $identifier;
		$this->socket = $socket;
		$addr = stream_socket_get_name($this->socket, true);
		$final = strrpos($addr, ":");
		$this->port = (int)substr($addr, $final + 1);
		$this->address = substr($addr, 0, $final);
	}

	/**
	 * @param int $threshold
	 */
	public function setCompression(int $threshold): void
	{
		$this->writeRaw(JavaBinarystream::writeJavaVarInt(0x03) . JavaBinarystream::writeJavaVarInt($threshold >= 0 ? $threshold : -1));
		$this->threshold = $threshold === -1 ? null : $threshold;
	}

	/**
	 * @param string $data
	 */
	public function writeRaw(string $data): void
	{
		if ($this->threshold !== null) {
			$dataLength = strlen($data);
			if ($dataLength >= $this->threshold) {
				$data = zlib_encode($data, ZLIB_ENCODING_DEFLATE, 7);
			} else {
				$dataLength = 0;
			}
			$data = JavaBinarystream::writeJavaVarInt($dataLength) . $data;
		}
		$this->write(JavaBinarystream::writeJavaVarInt(strlen($data)) . $data);
	}

	/**
	 * @param string $data
	 */
	public function write(string $data): void
	{
	    @fwrite($this->socket, $this->encryptionEnabled ? $this->encryptCipher->encryptUpdate($data) : $data);
	}

	/**
	 * @return string address
	 */
	public function getAddress(): string
	{
		return $this->address;
	}

	/**
	 * @return int port
	 */
	public function getPort(): int
	{
		return $this->port;
	}

	/**
	 * @param string $secret
	 */
	public function enableEncryption(string $secret): void
	{
		$algorithm = "AES-128-CFB8";
		$this->decryptCipher = new Cipher($algorithm);
		$this->decryptCipher->decryptInit($secret, $secret);
		$this->encryptCipher = new Cipher($algorithm);
		$this->encryptCipher->encryptInit($secret, $secret);
		$this->encryptionEnabled = true;
	}

	public function process(): void
	{
		$length = JavaBinarystream::readVarIntSession($this);
		if ($length === false or $this->status === -1) {
			$this->close("Connection closed");
			return;
		} elseif ($length <= 0 or $length > 131070) {
			$this->close("Invalid length");
			return;
		}

		$offset = 0;

		$buffer = $this->read($length);

		if ($this->threshold !== null) {
			$dataLength = JavaBinarystream::readComputerVarInt($buffer, $offset);
			if ($dataLength !== 0) {
				if ($dataLength < $this->threshold) {
					$this->close("Invalid compression threshold");
				} else {
					$buffer = zlib_decode(substr($buffer, $offset));
					$offset = 0;
				}
			} else {
				$buffer = substr($buffer, $offset);
				$offset = 0;
			}
		}

		if ($this->status === 2) { //Login
			$this->manager->sendPacket($this->identifier, $buffer);
		} elseif ($this->status === 1) {
			$pid = JavaBinarystream::readComputerVarInt($buffer, $offset);
			if ($pid === 0x00) {
				$sample = [];
				foreach ($this->manager->sample as $id => $name) {
					$sample[] = [
						"name" => $name,
						"id" => $id
					];
				}
				$data = [
					"version" => [
						"name" => InfoManager::VERSION,
						"protocol" => InfoManager::PROTOCOL
					],
					"players" => [
						"max" => $this->manager->getServerData()["MaxPlayers"],
						"online" => $this->manager->getServerData()["OnlinePlayers"],
						"sample" => $sample,
					],
					"description" => json_decode(Loader::toJSONInternal($this->manager->description))
				];
				if ($this->manager->favicon !== null) {
					$data["favicon"] = $this->manager->favicon;
				}
				$data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

				$data = JavaBinarystream::writeJavaVarInt(0x00) . JavaBinarystream::writeJavaVarInt(strlen($data)) . $data;
				$this->writeRaw($data);
			} elseif ($pid === 0x01) {
				$packet = new PingPacket();
				$packet->read($buffer, $offset);
				$this->writePacket($packet);
				$this->status = -1;
			}
		} elseif ($this->status === 0) {
			$pid = JavaBinarystream::readComputerVarInt($buffer, $offset);
			if ($pid === 0x00) {
				$protocol = JavaBinarystream::readComputerVarInt($buffer, $offset);
				$len = JavaBinarystream::readComputerVarInt($buffer, $offset);
				//host name
				$offset += $len;
				//server port
				$offset += 2;
				$nextState = JavaBinarystream::readComputerVarInt($buffer, $offset);

				if ($nextState === 1) {
					$this->status = 1;
				} elseif ($nextState === 2) {
					$this->status = -1;
					if($protocol < InfoManager::PROTOCOL){
						$packet = new LoginDisconnectPacket();
						$packet->reason = json_encode(["translate" => "multiplayer.disconnect.outdated_client", "with" => [["text" => infoManager::VERSION]]]);
						$this->writePacket($packet);
					}elseif($protocol > InfoManager::PROTOCOL){
						$packet = new LoginDisconnectPacket();
						$packet->reason = json_encode(["translate" => "multiplayer.disconnect.outdated_server", "with" => [["text" => infoManager::VERSION]]]);
						$this->writePacket($packet);
					}else{
						$this->manager->openSession($this);
						$this->status = 2;
					}
				} else {
					$this->close();
				}
			} else {
				$this->close("Unexpected packet $pid");
			}
		}
	}

	/**
	 * @param string $reason
	 */
	public function close(string $reason = ""): void
	{
		$this->manager->close($this);
	}

	/**
	 * @param int $len
	 * @return string data read from socket
	 */
	public function read(int $len): string
	{
		$data = @fread($this->socket, $len);
		if ($data !== false) {
			if ($this->encryptionEnabled && strlen($data) > 0) {
				return $this->decryptCipher->decryptUpdate($data);
			}
			return $data;
		}
		return "";
	}

	/**
	 * @param Packet $packet
	 */
	public function writePacket(Packet $packet): void
	{
		$this->writeRaw($packet->write());
	}

	/**
	 * @return int identifier
	 */
	public function getID(): int
	{
		return $this->identifier;
	}
}
