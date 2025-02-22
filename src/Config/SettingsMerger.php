<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Common\Arr;
use Glider88\Fixturization\Schema\Schema;
use Glider88\Fixturization\Schema\TableMeta;

readonly class SettingsMerger
{
    /** @var array<string> */
    private array $tables;

    public function __construct(
        private Schema $schema,
    ) {
        $this->tables = array_map(static fn(TableMeta $t) => $t->name, $this->schema->allTables());
    }

    public function merge(array $allSettings, array $entrypointSettings): array
    {
        $base = $allSettings['settings']['tables'] ?? [];
        $extra = $entrypointSettings['settings']['tables'] ?? [];

        $tables = [];
        foreach ($this->tables as $table) {
            $tables[$table]['count']        = $extra[$table]['count']        ?? $base[$table]['count']        ?? 1;
            $tables[$table]['columns']      = $extra[$table]['columns']      ?? $base[$table]['columns']      ?? null;
            $tables[$table]['transformers'] = $extra[$table]['transformers'] ?? $base[$table]['transformers'] ?? [];
            $tables[$table]['filter']       = $extra[$table]['filter']       ?? $base[$table]['filter']       ?? null;

            $tables[$table]['count'] = $this->define($tables[$table]['count']);
        }

        $joins = $allSettings['settings']['joins'] ?? $entrypointSettings['settings']['joins'] ?? [];

        return ['settings' => ['tables' => $tables, 'joins' => $joins]];
    }

    private function define(int|string $count): int
    {
        $count = (string) $count;
        $vals = explode('-', $count);
        if (count($vals) === 1) {
            return (int) Arr::first($vals);
        }

        return random_int((int) Arr::first($vals), (int) Arr::last($vals));
    }
}
