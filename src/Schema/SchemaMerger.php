<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

class SchemaMerger implements SchemaMergerInterface
{
    public function merge(array ...$schemas): array
    {
        /** @var array<string> $allTables */
        $allTables = array_unique(
            array_merge(...array_map(static fn(array $c) => array_keys($c), $schemas))
        );

        $result = [];
        foreach ($allTables as $table) {
            $pk = $this->takeLast('pk', $table, $schemas);
            if ($pk === null) {
                throw new \InvalidArgumentException("Missing 'pk' key for '$table'");
            }
            $result[$table]['pk'] = $pk;
            $result[$table]['columns'] = $this->takeLast('columns', $table, $schemas) ?: [];
            $result[$table]['foreign_keys'] = $this->mergeSettings('foreign_keys', $table, $schemas);
        }

        return $result;
    }

    private function takeLast(string $key, string $table, array $configs): mixed
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

    private function mergeSettings(string $key, string $table, array $configs): array
    {
        $results = [];
        foreach ($configs as $tableConf) {
            $results[] = $tableConf[$table][$key] ?? [];
        }

        return array_merge(...$results);
    }
}
