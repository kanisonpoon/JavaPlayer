<?php

declare(strict_types=1);

namespace pooooooon\javaplayer\network\protocol\Play\Server;

use pooooooon\javaplayer\network\OutboundPacket;

class UpdateScorePacket extends OutboundPacket
{

	const ACTION_ADD_OR_UPDATE = 0;
	const ACTION_REMOVE = 1;

	/** @var string */
	public $entry;
	/** @var int */
	public $action;
	/** @var string */
	public $objective;
	/** @var int */
	public $value;

	public function pid(): int
	{
		return self::UPDATE_SCORE_PACKET;
	}

	protected function encode(): void
	{
		$this->putString($this->entry);
		$this->putVarInt($this->action);
		$this->putString($this->objective);
		if ($this->action === self::ACTION_ADD_OR_UPDATE) {
			$this->putVarInt($this->value);
		}
	}
}
