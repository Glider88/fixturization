<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Symfony\Component\Yaml\Yaml;

readonly class EntrypointsFactory
{
    public function __construct(
        private Path $path,
    ) {}

    public function create(): Entrypoints
    {
        $entrypointConfig = Yaml::parseFile($this->path->configPath) ?? [];
        $entrypoints = $entrypointConfig['entrypoints'] ?? [];

        $tables = [];
        foreach ($entrypoints as $e) {
            $tables[$e['table']] = new Entrypoint($e['table'], $e['count'] ?? null);
        }

        return new Entrypoints($tables);
    }
}
