<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

readonly class Entrypoints
{
    /** @param array<string, Entrypoint> $tableToEntrypoint */
    public function __construct(
        private array $tableToEntrypoint,
    ) {}

    public function entrypoint(string $tableName): Entrypoint
    {
        if (! array_key_exists($tableName, $this->tableToEntrypoint)) {
            throw new \InvalidArgumentException("Table '$tableName' does not exist in Entrypoints");
        }

        return $this->tableToEntrypoint[$tableName];
    }

    /** @return array<string, Entrypoint> table name -> Entrypoint */
    public function all(): array
    {
        return $this->tableToEntrypoint;
    }
}
