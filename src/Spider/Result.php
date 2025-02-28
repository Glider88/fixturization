<?php declare(strict_types=1);

namespace Glider88\Fixturization\Spider;

use Glider88\Fixturization\Schema\TableMeta;

class Result
{
    private function __construct(
        /** @var array<string, array<int|string, array>> */
        public array $tableToRowIdToRow,
    ) {}

    public static function newEmpty(): Result
    {
        return new Result([]);
    }

    /** @param array<array> $rows */
    public static function new(TableMeta $schema, array $rows): Result
    {
        $result = [];
        foreach ($rows as $row) {
            $ids = [];
            foreach ($schema->pk as $pk) {
                $ids[] = $row[$pk];
            }
            $id = implode('|', $ids);
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
            foreach ($rows as $id => $row) {
                $this->tableToRowIdToRow[$tableName][$id] = $row;
            }
        }
    }

    /** @return array<string, array<int|string, array>> */
    public function result(): array
    {
        return $this->tableToRowIdToRow;
    }
}
