<?php declare(strict_types=1);

namespace Glider88\Fixturization\Spider;

use Glider88\Fixturization\Schema\TableSchema;

class Result
{
    public const MULTI_PK_SEPARATOR = '|';

    private function __construct(
        /** @var array<string, array<int|string, array>> */
        public array $tableToRowIdToRow,
    ) {}

    public static function newEmpty(): Result
    {
        return new Result([]);
    }

    /** @param array<array> $rows */
    public static function new(TableSchema $schema, array $rows): Result
    {
        $result = [];
        foreach ($rows as $row) {
            $ids = [];
            foreach ($schema->pks as $pk) {
                $ids[] = $row[$pk];
            }
            $id = implode(self::MULTI_PK_SEPARATOR, $ids);
            $result[$schema->name][$id] = $row;
        }

        return new Result($result);
    }

    /** @param array<Result> $results */
    public static function mergeAll(array $results): Result
    {
        if (empty($results)) {
            return self::newEmpty();
        }

        $first = array_shift($results);
        foreach ($results as $result) {
            $first->merge($result);
        }

        return $first;
    }

    public function merge(Result $result): void
    {
        foreach ($result->result() as $tableName => $rows) {
            foreach ($rows as $id => $newRow) {
                $row = $this->tableToRowIdToRow[$tableName][$id] ?? [];
                $this->tableToRowIdToRow[$tableName][$id] = array_merge($row, $newRow);
            }
        }
    }

    /** @return array<string, array<int|string, array>> */
    public function result(): array
    {
        return $this->tableToRowIdToRow;
    }
}
