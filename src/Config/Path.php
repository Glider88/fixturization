<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

readonly class Path {
    private function __construct(
        public ?string $entrypointPath = null,
        public ?string $fixtureYamlPath = null,
        public ?string $fixtureSqlPath = null,
        public ?string $schemaDbPath = null,
        public ?string $schemaManualPath = null,
    ) {}

    public static function newInstance(
        string $projectDir,
        ?string $entrypointPath = null,
        ?string $fixtureYamlPath = null,
        ?string $fixtureSqlPath = null,
        ?string $schemaDbPath = null,
        ?string $schemaManualPath = null,
    ): Path
    {
        $yamls = array_filter([
            $entrypointPath,
            $fixtureYamlPath,
            $schemaDbPath,
            $schemaManualPath,
        ]);

        foreach ($yamls as $yaml) {
            if (!in_array(pathinfo($yaml, PATHINFO_EXTENSION), ['yml', 'yaml'], true)) {
                throw new \InvalidArgumentException("Invalid file extension '$yaml', allow '*.yml' or '*.yaml'");
            }
        }

        $projectDir = str_ends_with($projectDir, '/') ? $projectDir : $projectDir . '/';
        $fn = static fn(?string $path) => $path === null ? null : $projectDir . $path;

        return new Path(
            entrypointPath: $fn($entrypointPath),
            fixtureYamlPath: $fn($fixtureYamlPath),
            fixtureSqlPath: $fn($fixtureSqlPath),
            schemaDbPath: $fn($schemaDbPath),
            schemaManualPath: $fn($schemaManualPath),
        );
    }
}
