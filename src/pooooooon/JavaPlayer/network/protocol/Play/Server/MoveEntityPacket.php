<?php

namespace pooooooon\javaplayer\network\protocol\Play\Server;

use pooooooon\javaplayer\network\OutboundPacket;

class MoveEntityPacket extends OutboundPacket
{
	/** @var int*/
	public $entityid;
	/** @var double*/
	public $moveX;
	/** @var double*/
	public $moveY;
	/** @var double*/
	public $moveZ;
	/** @var bool*/
	public $onground = true;

	public function pid(): int
	{
		return self::ENTITY_POSITION_PACKET;
	}

	protected function encode(): void
	{
		$this->putVarInt($this->entityid);
		$this->putShort($this->xa);
		$this->putShort((int) ($this->moveX * 4096));
		$this->putShort((int) ($this->moveY * 4096));
		$this->putShort((int) ($this->moveZ * 4096));
		$this->putBool($this->onGround);
	}
}
