<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

use Doctrine\DBAL\Connection;

readonly class PostgreSQL implements DatabaseInterface
{
    public function __construct(
        private Connection $connection
    ) {}

    public function tables(): array
    {
        $sql = <<<SQL
SELECT table_name
FROM information_schema.tables
WHERE table_type = 'BASE TABLE'
  AND table_schema NOT IN ('pg_catalog', 'information_schema')
SQL;

        return array_column(
            $this->connection->fetchAllAssociative($sql),
            'table_name'
        );
    }

    public function columns(string $table): array
    {
        $sql = <<<SQL
SELECT column_name
FROM information_schema.columns
WHERE table_name = '$table'
SQL;

        return array_column(
            $this->connection->fetchAllAssociative($sql),
            'column_name'
        );
    }

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

        return $this->connection->fetchAllAssociative($sql);
    }

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
            $this->connection->fetchAllAssociative($sql),
            'column_name'
        );

        if (count($pk) === 0) {
            throw new \RuntimeException("Table '$table' does not have a primary key");
        }

        return $pk;
    }

    public function randomRows(string $table, array $columns, ?int $count): array
    {
        $cols = implode(', ', $columns);
        if (empty($cols)) {
            $cols = '*';
        }

        $limitClause = $count === null ? '' : "limit $count";

        $sql = "select $cols from $table order by random() $limitClause";

        return $this->connection->fetchAllAssociative($sql);
    }

    public function row(string $table, array $columns, array $whereColumns, array $whereValues): array
    {
        $cols = implode(', ', $columns);
        if (empty($cols)) {
            $cols = '*';
        }

        if (count($whereColumns) !== count($whereValues)) {
            $wc = implode(', ', $whereColumns);
            $wv = implode(', ', $whereValues);
            throw new \RuntimeException("Values $wv does not match number of columns: $wc");
        }

        $fn = static function(string $col, $val): string
        {
            if (is_string($val)) {
                $val = "'$val'";
            }

            if (is_bool($val)) {
                $val = $val ? 'true' : 'false';
            }

            if (is_null($val)) {
                return "$col is null";
            }

            return "$col=$val";
        };

        $whereClause = implode(' AND ', array_map($fn, $whereColumns, $whereValues));

        $sql = "select $cols from $table where $whereClause";

        return $this->connection->fetchAllAssociative($sql);
    }

    public function setSeed(float $seed): void
    {
        $this->connection->executeQuery("select setseed($seed)");
    }

    public function unsetSeed(): void
    {
        $this->connection->executeQuery("select setseed(null)");
    }
}
