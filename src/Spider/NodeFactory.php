<?php declare(strict_types=1);

namespace Glider88\Fixturization\Spider;

use Glider88\Fixturization\Common\Arr;
use Glider88\Fixturization\Config\SettingsFactory;
use Glider88\Fixturization\Schema\Link;
use Glider88\Fixturization\Schema\Schema;

readonly class NodeFactory
{
    public function __construct(
        private Schema $schema,
        private SettingsFactory $settingsFactory,
    ) {}

    public function create(array $entrypointSettings): Node
    {
        return $this->nodeRec(null, $entrypointSettings, []);
    }

    private function nodeRec(?Link $link, array $entryConf, array $path): Node
    {
        $currentTable = $link?->table ?: $entryConf['start'];
        $path[] = $currentTable;
        $settings = $this->calculateSettings($currentTable, $entryConf, $path);
        $alias = uniqid('p', false);
        $tableSettings = $this->settingsFactory->create($currentTable, $link, $alias, $settings);
        $tree = $entryConf[$currentTable]['tree'] ?? null;

        $children = [];
        foreach ($this->schema->allTables() as $table) {
            foreach ($this->schema->links($currentTable, $table) as $childLink) {
                $copiedPath = $path;
                if ($tree) {
                    $copiedPath = Arr::rtrim($copiedPath, [$childLink->table], $tree);
                }

                $visited = in_array($childLink->table, $copiedPath, true);
                if ($visited) {
                    continue;
                }

                $children[] = $this->nodeRec($childLink, $entryConf, $path);
            }
        }

        return new Node(
            name: $currentTable,
            alias: $alias,
            link: $link,
            schema: $this->schema->table($currentTable),
            settings: $tableSettings,
            children: $children,
        );
    }

    private function calculateSettings(string $currentTable, array $entryConf, array $path): array
    {
        $tableConf = $entryConf[$currentTable] ?? [];
        $routeConf = $entryConf['route-settings'] ?? [];

        usort($routeConf, static function (array $a, array $b) {
            return count($b['route'] ?? []) <=> count($a['route'] ?? []);
        });

        $result = $tableConf;
        foreach ($routeConf as $route) {
            $len = count($route['route']);
            $last = Arr::sliceLast($path, $len);
            if ($route['route'] === $last) {
                $merged = [];
                if (array_key_exists('filter', $route) || array_key_exists('filter', $result)) {
                    $merged['filter'] = $route['filter'] ?? $result['filter'];
                }

                if (array_key_exists('filter', $route) || array_key_exists('filter', $result)) {
                    $merged['count'] = $route['count'] ?? $result['count'];
                }

                return $merged;
            }
        }

        return $result;
    }
}
