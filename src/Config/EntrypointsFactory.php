<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Spider\Node;
use Symfony\Component\Yaml\Yaml;

readonly class EntrypointsFactory
{
    public function __construct(
        private Path $path,
    ) {}

    /** @return array<Entrypoint> */
    public function create(): array
    {
        $config = Yaml::parseFile($this->path->configPath) ?? [];
        $entrypointConfig = $config['entrypoints'] ?? [];

        $entrypoints = [];
        foreach ($entrypointConfig as $e) {
            $entrypoints[] = new Entrypoint($this->node($e['routes']), $e['count']);
        }

        return $entrypoints;
    }

    /**
     * @param array<array<string>> $routes
     * @return array<Node>
     */
    private function node(array $routes): array
    {
        $headFn = static fn(array $array) => current($array);
        $tailFn = static fn(array $array) => array_slice($array, 1);

        $headToRoutes = [];
        foreach ($routes as $route) {
            if (empty($route)) {
                continue;
            }

            $headToRoutes[$headFn($route)][] = $tailFn($route);
        }

        $result = [];
        foreach ($headToRoutes as $head => $tail) {
            $result[] = new Node($head, $this->node($tail));
        }

        return $result;
    }
}
