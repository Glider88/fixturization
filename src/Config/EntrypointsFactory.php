<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Common\Arr;
use Glider88\Fixturization\Spider\Node;

readonly class EntrypointsFactory
{
    public function __construct(
        private array $config,
        private SettingsMerger $merger,
        private SettingsFactory $settingsFactory,
    ) {}

    /** @return array<Entrypoint> */
    public function create(): array
    {
        $entrypointConfig = $this->config['entrypoints'] ?? [];

        $entrypoints = [];
        foreach ($entrypointConfig as $entry) {
            $merged = $this->merger->merge($this->config, $entry);
            $settings = $this->settingsFactory->create($merged);
            $routes = $this->enrichWithJoins($entry['routes'], $settings->joins);
            $entrypoints[] = new Entrypoint($this->node($routes), $settings);
        }

        return $entrypoints;
    }

    /**
     * @param array<array<string>> $routes
     * @param array<array<string>> $joins
     * @return array<array<string>>
     */
    private function enrichWithJoins(array $routes, array $joins): array
    {
        foreach ($routes as &$route) {
            foreach ($joins as $k => $join) {
                $length = count($join);
                $windows = Arr::slidingWindow($route, $length);
                foreach ($windows as $i => $window) {
                    if ($window === $join) {
                        $startJoin = array_shift($join);
                        $startJoin = "=$k=$startJoin";
                        array_unshift($join, $startJoin);
                        array_splice($route, $i, $length, $join);
                    }
                }
            }
        }

        return $routes;
    }

    /**
     * @param array<array<string>> $routes
     * @return array<Node>
     */
    private function node(array $routes): array
    {
        $headToRoutes = [];
        $headToJoinIndex = [];
        foreach ($routes as $route) {
            if (empty($route)) {
                continue;
            }

            $head = Arr::head($route);
            if (preg_match('/^=(\d+)=(.*)$/', $head, $matches)) {
                $head = $matches[2];
                $headToJoinIndex[$head] = (int) $matches[1];
            }

            $headToRoutes[$head][] = Arr::tail($route);
        }

        $result = [];
        foreach ($headToRoutes as $head => $tail) {
            $joinIndex = $headToJoinIndex[$head] ?? null;
            $result[] = new Node($head, $joinIndex, $this->node($tail));
        }

        return $result;
    }
}
