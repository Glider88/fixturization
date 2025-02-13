<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

readonly class TableSettings
{
    /** @param array<string, ColumnSettings> $columnToColumnSettings */
    public function __construct(
        private array $columnToColumnSettings,
        private ?int $count,
    ) {}

    public function count(): int
    {
        return $this->count ?: 1;
    }

    public function columnSettings(string $columnName): ?ColumnSettings
    {
        return $this->columnToColumnSettings[$columnName] ?? null;
    }

    /** @return array<string, ColumnSettings> column name -> ColumnSettings */
    public function all(): array
    {
        return $this->columnToColumnSettings;
    }
}
