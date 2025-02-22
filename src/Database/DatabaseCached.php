<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

use Glider88\Fixturization\Config\Settings;

class DatabaseCached implements DatabaseRowInterface
{
    /** @var array<string, array<int|string, array<string, mixed>>> */
    private array $tableToIdToRow = [];

    public function __construct(
        readonly private DatabaseRowInterface $db,
        readonly Cache                        $cache,
    ) {}

    /** @param array<WhereLinkClause> $whereClauses */
    public function fetchCache(int $joinIndex, Settings $settings, bool $random, array $whereClauses): void
    {
        $tableToIdToRow = $this->cache->fetch($joinIndex, $settings, $random, $whereClauses);
        foreach ($tableToIdToRow as $table => $idToRow) {
            foreach ($idToRow as $id => $row) {
                $this->tableToIdToRow[$table][$id] = $row;
            }
        }
    }

    /** @inheritDoc */
    public function rows(string $table, array $columns, int $limit, array $whereClauses, bool $random): array
    {
        $cache = $this->fromCache($table, $columns, $limit, $whereClauses);
        if (empty($cache)) {
            return $this->db->rows($table, $columns, $limit, $whereClauses, $random);
        }

        return $cache;
    }

    /**
     * @param array<string> $columns
     * @param array<WhereClauseInterface> $whereClauses
     * @return array<array<string, mixed>>
     */
    private function fromCache(string $table, array $columns, int $limit, array $whereClauses): array
    {
        /** @var array<WhereLinkClause> $wheres */
        $wheres = array_filter($whereClauses, static fn(WhereClauseInterface $w) => $w instanceof WhereLinkClause);

        $rows = $this->tableToIdToRow[$table] ?? [];

        $filterFn =
            static fn(WhereLinkClause $where) =>
                static fn(array $row) =>
                    array_key_exists($where->column, $row) && $row[$where->column] === $where->value;

        foreach ($wheres as $where) {
            $rows = array_filter($rows, $filterFn($where));
        }

        $columnAsKeys = array_flip($columns);
        $result = [];
        foreach ($rows as $id => $colToVal) {
            $result[$id] = array_intersect_key($colToVal, $columnAsKeys);
        }

        return array_slice($result, 0, $limit);
    }
}
