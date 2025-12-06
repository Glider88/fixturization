<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

readonly class SettingsMerger
{
    /** @param list<string> $tables */
    public function __construct(
        private array $tables,
    ) {}

    public function enrichSettings(array $config): array
    {
        $base = $config['base_settings'] ?? [];
        $entrypoints = $config['entrypoints'] ?? [];

        $result = [];
        foreach ($entrypoints as $entry) {
            $result[] = $this->mergeEntrypointWithBase($base, $entry);
        }

        return $result;
    }

    private function mergeEntrypointWithBase(array $base, array $entrypoint): array
    {
        $result = $base;
        $result['start'] = $entrypoint['start'] ?? $base['start'];

        foreach ($this->tables as $table) {
            $b = $base[$table] ?? [];
            $e = $entrypoint[$table] ?? [];

            $be = $b['exclude_columns'] ?? [];
            $ee = $e['exclude_columns'] ?? [];
            $r['exclude_columns'] = array_unique(array_merge($be, $ee));
            $r['columns']      = $e['columns']      ?? $b['columns']      ?? [];
            $r['filter']       = $e['filter']       ?? $b['filter']       ?? null;
            $r['count']        = $e['count']        ?? $b['count']        ?? null;
            $r['transformers'] = $e['transformers'] ?? $b['transformers'] ?? [];
            $r['tree']         = $e['tree']         ?? $b['tree']         ?? null;

            $merged = array_filter($r, static fn($s) => $s !== null && $s !== []);
            if ($merged) {
                $result[$table] = $merged;
            }
        }

        $routeFilter = $this->mergeRouteSettings($base, $entrypoint);
        if ($routeFilter) {
            $result['route-settings'] = $routeFilter;
        }

        return $result;
    }

    private function mergeRouteSettings(array $base, array $entry): array
    {
        $base = $base['route-settings'] ?? [];
        $entry = $entry['route-settings'] ?? [];

        $allKeys = [];

        $baseK = [];
        foreach ($base as $val) {
            $route = implode('|', $val['route']);
            $allKeys[] = $route;
            $baseK[$route] = $val;
        }

        $entryK = [];
        foreach ($entry as $val) {
            $route = implode('|', $val['route']);
            $allKeys[] = $route;
            $entryK[$route] = $val;
        }

        $result = [];
        $allKeys = array_unique($allKeys);
        foreach ($allKeys as $key) {
            $result[] = array_merge($baseK[$key] ?? [], $entryK[$key] ?? []);
        }

        return $result;
    }
}
