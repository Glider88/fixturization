<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

readonly class Settings
{
    /**
     * @param array<string, TableSettings> $tableToTableSettings
     * @param array<array<string>> $joins
     */
    public function __construct(
        private array $tableToTableSettings,
        public array $joins,
    ) {}

    public function tableSettings(string $tableName): TableSettings
    {
        return $this->tableToTableSettings[$tableName];
    }
}
