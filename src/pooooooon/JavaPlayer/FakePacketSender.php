<?php

declare(strict_types=1);

namespace pooooooon\javaplayer;

use pocketmine\network\mcpe\PacketSender;

final class FakePacketSender implements PacketSender
{

	public function send(string $payload, bool $immediate): void
	{
	}

	public function close(string $reason = "unknown reason"): void
	{
	}
}