<?php

declare(strict_types=1);

namespace pooooooon\javaplayer\network\protocol\Play\Server;

use pooooooon\javaplayer\network\OutboundPacket;

class ScoreboardObjectivePacket extends OutboundPacket
{

	const ACTION_ADD = 0;
	const ACTION_REMOVE = 1;
	const ACTION_UPDATE = 2;

	const TYPE_INTEGER = 0;
	const TYPE_HEARTS = 1;

	/** @var string */
	public $name;
	/** @var int */
	public $action;
	/** @var string */
	public $displayName;
	/** @var int */
	public $type;

	public function pid(): int
	{
		return self::SCOREBOARD_OBJECTIVE_PACKET;
	}

	protected function encode(): void
	{
		$this->putString($this->name);
		$this->putByte($this->action);
		if ($this->action === self::ACTION_ADD || $this->action === self::ACTION_UPDATE) {
			$this->putString($this->displayName);
			$this->putVarInt($this->type);
		}
	}
}
