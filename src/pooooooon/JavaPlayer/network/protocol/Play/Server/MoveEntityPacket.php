<?php


namespace pooooooon\javaplayer\network\protocol\Play\Server;


use pooooooon\javaplayer\network\OutboundPacket;

class MoveEntityPacket extends OutboundPacket
{
	/** @var int $entityid */
	public $entityid;
	/** @var int $xa */
	public $xa;
	/** @var int $ya */
	public $ya;
	/** @var int $za */
	public $za;
	/** @var int $yaw */
	public $yaw;
	/** @var int $pitch */
	public $pitch;
	/** @var bool onGround */
	public $onground = true;
	/** @var bool onGround */
	public $hasyaw = true;
	/** @var bool onGround */
	public $haspitch = true;

	public function pid(): int
	{
		return self::ENTITY_POSITION_PACKET;//Entity_move_packet
	}

	protected function encode(): void
	{
		$this->putVarInt($this->entityid);
		$this->putShort($this->xa);

	}
}
