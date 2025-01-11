<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

readonly class Schema
{
    /** @param array<string, TableMeta> $tableToMeta */
    public function __construct(
        private array $tableToMeta,
    ) {}

    public function table(string $tableName): TableMeta
    {
        if (! array_key_exists($tableName, $this->tableToMeta)) {
            throw new \InvalidArgumentException("Table '$tableName' does not exist in Schema");
        }

        return $this->tableToMeta[$tableName];
    }
}
