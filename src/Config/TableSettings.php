<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

readonly class TableSettings
{
    /** @param array<string, ColumnSettings> $tableToColumnSettings */
    public function __construct(
        private array $tableToColumnSettings,
    ) {}

    public function columnSettings(string $tableName): ColumnSettings
    {
        if (! array_key_exists($tableName, $this->tableToColumnSettings)) {
            throw new \InvalidArgumentException("Table '$tableName' does not exist in TableSettings");
        }

        return $this->tableToColumnSettings[$tableName];
    }

    /** @return array<string, ColumnSettings> table name -> ColumnSettings */
    public function all(): array
    {
        return $this->tableToColumnSettings;
    }
}
