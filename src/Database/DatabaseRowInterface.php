<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

interface DatabaseRowInterface
{
    /**
     * @param array<string> $columns
     * @param array<WhereClauseInterface> $whereClauses
     * @return array<array<string, mixed>>
     */
    public function rows(string $table, array $columns, int $limit, array $whereClauses, bool $random): array;
}
