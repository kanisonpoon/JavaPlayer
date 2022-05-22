<?php

declare(strict_types=1);

namespace pooooooon\javaplayer\nbt\tag;

use pocketmine\nbt\NbtStreamReader;
use pocketmine\nbt\NbtStreamWriter;
use pocketmine\nbt\ReaderTracker;
use pocketmine\nbt\tag\ImmutableTag;
use function assert;
use function implode;
use function is_int;

class LongArrayTag extends ImmutableTag
{
	/** @var int[] */
	private $value;

	/**
	 * @param string $name
	 * @param int[] $value
	 */
	public function __construct(array $value = [])
	{
		self::restrictArgCount(__METHOD__, func_num_args(), 1);

		assert((function () use (&$value): bool {
			foreach ($value as $v) {
				if (!is_int($v)) {
					return false;
				}
			}

			return true;
		})());

		$this->value = $value;
	}

	public function getType(): int
	{
		return 12;//LongArray
	}

	public function read(NbtStreamReader $nbt, ReaderTracker $tracker): void
	{
		//Not implement
	}

	public function write(NbtStreamWriter $writer): void
	{
		//Not implement
	}

	/**
	 * @return int[]
	 */
	public function getValue(): array
	{
		return $this->value;
	}

	protected function getTypeName(): string
	{
		return "LongArray";
	}

	/*public function toString(int $indentation = 0) : string{
		return str_repeat("  ", $indentation) . get_class($this) . ": " . ($this->__name !== "" ? "name='$this->__name', " : "") . "value=[" . implode(",", $this->value) . "]";
	}*/

	protected function stringifyValue(int $indentation): string
	{
		return "[" . implode(",", $this->value) . "]";
	}
}
