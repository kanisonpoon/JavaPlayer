<?php

declare(strict_types=1);

namespace pooooooon\javaplayer;

use pocketmine\player\Player;
use pocketmine\Server;
use pooooooon\javaplayer\network\JavaPlayerNetworkSession;

final class JavaPlayer
{

	public Loader $loader;
	public $status = 0;
	private JavaPlayerNetworkSession $session;
	private Player $player;
	/** @var array<string, mixed> */
	private array $metadata = [];

	public function __construct(JavaPlayerNetworkSession $session, Loader $loader)
	{
		$this->session = $session;
		$this->loader = $loader;
		$this->player = $session->getPlayer();
	}

	public function getServer(): Server
	{
		return Server::getInstance();
	}

	public function getPlayer(): Player
	{
		return $this->player;
	}

	public function getPlayerNullable(): ?Player
	{
		return $this->player;
	}

	public function destroy(): void
	{
		$this->metadata = [];
	}

	public function getNetworkSession(): JavaPlayerNetworkSession
	{
		return $this->session;
	}

	public function tick(): void
	{
		//TODO:
	}

	public function getMetadata(string $key, mixed $default = null): mixed
	{
		return $this->metadata[$key] ?? $default;
	}

	public function setMetadata(string $key, mixed $value): void
	{
		$this->metadata[$key] = $value;
	}

	public function deleteMetadata(string $key): void
	{
		unset($this->metadata[$key]);
	}

}
