<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

use Glider88\Fixturization\Database\DatabaseInterface;

readonly class DatabaseSchemaGenerator implements SchemaGeneratorInterface
{
    public function __construct(
        private DatabaseInterface $database,
    ) {}

    public function generate(): array
    {
        $tables = $this->database->tables();
        $result = [];
        foreach ($tables as $table) {
            $primaryKeys = $this->database->primaryKeys($table);
            if (count($primaryKeys) === 1) {
                $primaryKey = current($primaryKeys);
                $result[$table]['pk'] = $primaryKey;
            } else {
                $result[$table]['pk'] = $primaryKeys;
            }

            $result[$table]['columns'] = $this->database->columns($table);

            $foreignKeys = $this->database->foreignKeys($table);
            $foreignKeyColumns = array_column($foreignKeys, 'column_name');
            $fk = array_map(
                static fn($row) => $row['foreign_table_name'],
                array_combine($foreignKeyColumns, array_values($foreignKeys))
            );

            if (count($fk) > 0) {
                $result[$table]['foreign_keys'] = $fk;
            }
        }

        return $result;
    }
}
