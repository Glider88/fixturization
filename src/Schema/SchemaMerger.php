<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

class SchemaMerger implements SchemaMergerInterface
{
    public static function merge(array ...$schemas): array
    {
        $allTables = self::allTables($schemas);

        $result = [];
        foreach ($allTables as $table) {
            $pk = self::takeLast('pk', $table, $schemas);
            if ($pk === null) {
                throw new \InvalidArgumentException("Missing 'pk' key for '$table'");
            }
            $result[$table]['name'] = $table;
            $result[$table]['pk'] = (array) $pk;
            $result[$table]['columns'] = self::takeLast('columns', $table, $schemas) ?: [];
            $result[$table]['foreign_keys'] = self::mergeForeignKeys($table, $schemas);
        }

        return $result;
    }

    private static function takeLast(string $key, string $table, array $configs): mixed
    {
        $reversed = array_reverse($configs);
        foreach ($reversed as $tableConf) {
            $config = $tableConf[$table] ?? [];
            if (array_key_exists($key, $config)) {
                return $config[$key];
            }
        }

        return null;
    }

    private static function mergeForeignKeys(string $table, array $configs): array
    {
        $results = [];
        foreach ($configs as $tableConf) {
            $results[] = $tableConf[$table]['foreign_keys'] ?? [];
        }

        return array_merge(...$results);
    }

    /**
     * @param array<string, mixed> $schemas
     * @return list<string>
     */
    private static function allTables(array $schemas): array
    {
        $allTables = [];
        foreach ($schemas as $schema) {
            foreach ($schema as $table => $_) {
                $allTables[] = $table;
            }
        }

        return array_unique($allTables);
    }
}
