<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

use Doctrine\DBAL\Connection;
use Glider88\Fixturization\Common\Arr;
use Glider88\Fixturization\Config\Settings;
use Glider88\Fixturization\Schema\Schema;

// ToDo: обход в ширину и глубину в итератор?
// ToDo: WhereLinkClause для мульти pk и film - language
readonly class Cache
{
    public function __construct(
        private Connection $con,
        private Schema $schema,
    ) {}

    /**
     * @param array<WhereLinkClause> $whereClauses
     * @return array<string, array<int|string, array<string, mixed>>>
     */
    public function fetch(int $joinIndex, Settings $settings, bool $random, array $whereClauses): array
    {
        $joins = $settings->joins[$joinIndex];
        $sql = $this->sql($joins, $settings, $random, $whereClauses);
        $rows = $this->con->fetchAllAssociative($sql);

        return $this->cache($rows);
    }

    /** @return array<string, array<int|string, array<string, mixed>>> */
    private function cache(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $tableToColumnToVal = [];
            foreach ($row as $column => $value) {
                [$table, $col] = explode('_', $column, 2);
                $tableToColumnToVal[$table][$col] = $value;
            }

            foreach ($tableToColumnToVal as $table => $colToVal) {
                $pks = $this->schema->table($table)->pk;
                $ids = [];
                foreach ($pks as $pk) {
                    $ids[] = $colToVal[$pk];
                }
                $ids = implode('|', $ids);
                $result[$table][$ids] = $colToVal;
            }
        }

        return $result;
    }

    /**
     * @param non-empty-array<string> $joinTables
     * @param array<WhereLinkClause> $whereClauses
     */
    private function sql(array $joinTables, Settings $settings, bool $random, array $whereClauses): string
    {
        $selects = [];
        $joins = [];
        $wheres = [];
        $limits = [];

        $fromTable = Arr::head($joinTables);
        foreach ($whereClauses as $whereClause) {
            $wheres[] = "$fromTable.$whereClause->column = $whereClause->value";
        }

        foreach ($joinTables as $joinTable) {
            $tableSettings = $settings->tableSettings($joinTable);
            foreach ($tableSettings->columns as $column) {
                $selects[] = "$joinTable.$column {$joinTable}_$column";
            }

            if ($tableSettings->whereFilter !== null) {
                $wheres[] = (string) $tableSettings->whereFilter;
            }

            $limits[] = $tableSettings->count;
        }

        foreach (Arr::slidingWindow($joinTables, 2) as [$prevTable, $joinTable]) {
            $links = $this->schema->links($prevTable, $joinTable) ?: [];
            foreach ($links as $link) {
                $joins[] = "INNER JOIN $joinTable ON $link->ownTable.$link->ownColumn = $link->linkTable.$link->linkColumn";
            }
        }

        $selectClause = 'SELECT' . PHP_EOL . implode(',' . PHP_EOL, $selects) . PHP_EOL;
        $fromClause = 'FROM ' . Arr::head($joinTables) . PHP_EOL . implode(PHP_EOL, $joins) . PHP_EOL;

        $whereClause = '';
        if (!empty($wheres)) {
            $whereClause = 'WHERE' . PHP_EOL . implode(PHP_EOL . 'AND ', $wheres) . PHP_EOL;
        }

        $orderClause = '';
        if ($random) {
            $orderClause = 'ORDER BY RANDOM()' . PHP_EOL;
        }

        $limitClause = 'LIMIT ' . array_product($limits) . PHP_EOL;

        return "$selectClause$fromClause$whereClause$orderClause$limitClause";
    }

    // ToDo: find place
    public static function print_all_routes(string $table, Schema $schema, array $path = [], ?array $tables = null): void
    {
        $path[] = $table;
        if ($tables === null) {
            $tables = $schema->allTables();
        }

        $children = [];
        foreach ($tables as $k => $t) {
            $ls = $schema->links($table, $t->name);
            if (!empty($ls)) {
                foreach ($ls as $l) {
                    $children[] = $l->linkTable;
                }
                unset($tables[$k]);
            }
        }

        if(empty($children)) {
            echo '- [' . implode(', ', $path) . ']' . PHP_EOL;
        }

        foreach ($children as $child) {
            self::print_all_routes($child, $schema, $path, $tables);
        }
    }
}
