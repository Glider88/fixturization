<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

readonly class Settings
{
    /** @param array<string, TableSettings> $tableToTableSettings */
    public function __construct(
        private array $tableToTableSettings,
    ) {}

    public function tableSettings(string $tableName): TableSettings
    {
        return $this->tableToTableSettings[$tableName];
    }
}
