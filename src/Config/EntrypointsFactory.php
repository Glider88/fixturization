<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Common\Arr;
use Glider88\Fixturization\Spider\Node;
use Symfony\Component\Yaml\Yaml;

readonly class EntrypointsFactory
{
    public function __construct(
        private Path $path,
        private SettingsMerger $merger,
        private SettingsFactory $settingsFactory,
    ) {}

    /** @return array<Entrypoint> */
    public function create(): array
    {
        $config = Yaml::parseFile($this->path->configPath) ?? [];
        $entrypointConfig = $config['entrypoints'] ?? [];

        $entrypoints = [];
        foreach ($entrypointConfig as $e) {
            $settingsConfig = $e['settings'] ?? [];
            $merged = $this->merger->merge($settingsConfig);
            $settings = $this->settingsFactory->create($merged);
            $entrypoints[] = new Entrypoint($this->node($e['routes']), $settings);
        }

        return $entrypoints;
    }

    /**
     * @param array<array<string>> $routes
     * @return array<Node>
     */
    private function node(array $routes): array
    {
        $headToRoutes = [];
        foreach ($routes as $route) {
            if (empty($route)) {
                continue;
            }

            $headToRoutes[Arr::head($route)][] = Arr::tail($route);
        }

        $result = [];
        foreach ($headToRoutes as $head => $tail) {
            $result[] = new Node($head, $this->node($tail));
        }

        return $result;
    }
}
