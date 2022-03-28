<?php

declare(strict_types=1);

namespace pooooooon\javaplayer\network\listener;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ClientboundPacket;

interface JavaPlayerPacketListener
{

	public function onPacketSend(ClientboundPacket $packet, NetworkSession $session): void;
}