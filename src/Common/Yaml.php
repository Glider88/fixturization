<?php declare(strict_types=1);

namespace Glider88\Fixturization\Common;

use Symfony\Component\Yaml\Yaml as SymfonyYaml;

readonly class Yaml
{
    public static function parse(?string $path): array
    {
        if ($path === null) {
            return [];
        }

        return SymfonyYaml::parseFile($path) ?? [];
    }
}
