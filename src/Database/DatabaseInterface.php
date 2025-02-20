<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

interface DatabaseInterface
{
    /** @return array<string> */
    public function tables(): array;

    /** @return array<string> */
    public function columns(string $table): array;

    /** @return array<string> */
    public function foreignKeys(string $table): array;

    /** @return array<string> */
    public function primaryKeys(string $table): array;

    public function randomRows(string $table, array $columns, ?int $count): array;

    /** @param array<string> $columns */
    public function row(string $table, array $columns, int $limit, WhereClause ...$whereClauses): array;

    public function setSeed(float $seed): void;

    public function unsetSeed(): void;
}
