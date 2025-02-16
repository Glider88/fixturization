<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

readonly class Schema
{
    /**
     * @param array<string, TableMeta> $tableToMeta
     * @param array<string, array<string, Link>> $links
     */
    public function __construct(
        private array $tableToMeta,
        private array $links,
    ) {}

    public function table(string $tableName): TableMeta
    {
        if (! array_key_exists($tableName, $this->tableToMeta)) {
            throw new \InvalidArgumentException("Table '$tableName' does not exist in Schema");
        }

        return $this->tableToMeta[$tableName];
    }

    /** @return array<TableMeta> */
    public function allTables(): array
    {
        return $this->tableToMeta;
    }

    public function link(string $tableFrom, string $tableTo): ?Link
    {
        return $this->links[$tableFrom][$tableTo] ?? null;
    }
}
