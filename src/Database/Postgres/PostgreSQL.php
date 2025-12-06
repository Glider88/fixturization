<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database\Postgres;

use Doctrine\DBAL\Connection;
use Glider88\Fixturization\Database\DatabaseEntrypointInterface;
use Glider88\Fixturization\Database\DatabaseMetaInterface;
use Glider88\Fixturization\Database\DatabaseRowInterface;
use Glider88\Fixturization\Database\Query\GroupByClause;
use Glider88\Fixturization\Database\Query\Query;
use Glider88\Fixturization\Database\Query\SelectClause;
use Glider88\Fixturization\Database\Query\WhereClauseInterface;
use Glider88\Fixturization\Spider\Node;
use Psr\Log\LoggerInterface;

readonly class PostgreSQL implements DatabaseMetaInterface, DatabaseRowInterface, DatabaseEntrypointInterface
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
        private bool $dryRun = false,
        private bool $random = false,
        ?float $seed = null,
    ) {
        if ($seed !== null) {
            $this->executeQuery("select setseed($seed)");
        }
    }

    /** @inheritDoc */
    public function tables(): array
    {
        $sql = <<<SQL
            SELECT pc.relname AS table_name
            FROM pg_catalog.pg_class pc
            JOIN pg_catalog.pg_namespace pn
              ON pn.oid = pc.relnamespace
            WHERE (pc.relkind = 'p' OR pc.relkind = 'r')
              AND pc.relispartition = FALSE
              AND pn.nspname NOT IN ('pg_catalog', 'information_schema');
            SQL;

        return array_column(
            $this->fetchAllAssociative($sql),
            'table_name'
        );
    }

    /** @inheritDoc */
    public function columns(string $table): array
    {
        $sql = <<<SQL
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = '$table'
            SQL;

        return array_column(
            $this->fetchAllAssociative($sql),
            'column_name'
        );
    }

    /** @inheritDoc */
    public function foreignKeys(string $table): array
    {
        $sql = <<<SQL
            SELECT
              kcu.column_name,
              ccu.table_name AS foreign_table_name
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
              ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
              ON ccu.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY'
              AND tc.table_schema='public'
              AND tc.table_name='$table';
            SQL;

        return $this->fetchAllAssociative($sql);
    }

    /** @inheritDoc */
    public function primaryKeys(string $table): array
    {
        $sql = <<<SQL
            SELECT kcu.column_name
            FROM information_schema.table_constraints tc
              JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
            WHERE tc.table_schema = 'public'
              AND tc.constraint_type = 'PRIMARY KEY'
              AND tc.table_name = '$table';
            SQL;

        $pk = array_column(
            $this->fetchAllAssociative($sql),
            'column_name'
        );

        if (count($pk) === 0) {
            throw new \RuntimeException("Table '$table' does not have a primary key");
        }

        return $pk;
    }

    /** @inheritDoc */
    public function rows(Node $node, array $whereClauses): array
    {
        $limit = $node->settings->count;
        if ($limit === 0) {
            return [];
        }

        $selects = [];
        foreach ($node->settings->columns as $column) {
            $selects[] = new SelectClause($node->alias, $column);
        }

        $select = implode(',' . PHP_EOL, $selects);
        $where = $this->where($whereClauses);
        $order = $this->order();

        $sql = <<<SQL
            SELECT
            $select
            FROM $node->name $node->alias
            $where
            $order
            LIMIT {$node->settings->count}
            SQL;

        $rawRows = $this->fetchAllAssociative($sql);

        $result = [];
        foreach ($rawRows as $i => $rawRow) {
            foreach ($rawRow as $aliasColumn => $value) {
                $column = str_replace($node->alias . '_', '', $aliasColumn);
                $result[$i][$column] = $value;
            }
        }

        return $result;
    }

    /** @inheritDoc */
    public function entrypointIds(Node $start, Query $query): array
    {
        $groupBy = new GroupByClause($start->alias, $start->schema->pks);
        $join = implode(PHP_EOL, $query->joins);
        $where = $this->where($query->wheres);
        $order = $this->order();

        $sql = <<<SQL
            SELECT $groupBy
            FROM $start->name $start->alias
            $join
            $where
            GROUP BY $groupBy
            $order
            LIMIT $query->limit
            SQL;

        return $this->fetchAllAssociative($sql);
    }

    /** @inheritDoc */
    public function entrypointCache(Query $query, array $entrypointWheres = []): array
    {
        $select = implode(',' . PHP_EOL, $query->select);
        $join = implode(PHP_EOL, $query->joins);
        $where = $this->where(array_merge($query->wheres, $entrypointWheres));
        $order = $this->order();

        $sql = <<<SQL
            SELECT
            $select
            FROM $query->table $query->alias
            $join
            $where
            $order
            LIMIT 1
            SQL;

        $rawRows = $this->fetchAllAssociative($sql);

        $result = [];
        foreach ($rawRows as $i => $rawRow) {
            foreach ($rawRow as $aliasColumn => $value) {
                [$alias, $column] = explode('_', $aliasColumn, 2);
                $result[$alias][$i][$column] = $value;
            }
        }

        return $result;
    }

    private function order(): string
    {
        $result = '';
        if ($this->random) {
            $result = 'ORDER BY RANDOM()';
        }

        return $result;
    }

    /** @param list<WhereClauseInterface> $wheres */
    private function where(array $wheres): string
    {
        $result = '';
        if ($wheres) {
            $result = 'WHERE' . PHP_EOL . '  ' . implode(PHP_EOL . '  AND ', $wheres);
        }

        return $result;
    }

    private function executeQuery(string $sql): void
    {
        $this->logger->debug(PHP_EOL . PHP_EOL . $sql);
        if ($this->dryRun) {
            return;
        }

        $this->connection->executeQuery($sql);
    }

    private function fetchAllAssociative(string $sql): array
    {
        $this->logger->debug(PHP_EOL . PHP_EOL . $sql);
        if ($this->dryRun) {
            return [];
        }

        return $this->connection->fetchAllAssociative($sql);
    }
}
