<?php declare(strict_types=1);

namespace Glider88\Fixturization\FileGenerator;

use Glider88\Fixturization\Config\Path;
use Symfony\Component\Yaml\Yaml;

readonly class FileSaver
{
    public function __construct(
        private Path $path,
    ) {}
    
    public function saveSchemaAutoDd(array $schema): void
    {
        $path = $this->path->schemaDbPath;
        $this->prepare($path);
        file_put_contents($path, Yaml::dump($schema, 4, 2));
    }

    public function saveFixtureSql(string $fixtures): void
    {
        $path = $this->path->fixtureSqlPath;
        $this->prepare($path);
        file_put_contents($path, $fixtures);
    }

    public function saveFixtureYaml(array $fixtures): void
    {
        $path = $this->path->fixtureYamlPath;
        $this->prepare($path);
        file_put_contents($path, Yaml::dump($fixtures, 4, 2));
    }

    private function prepare(?string $path): void
    {
        if (null === $path) {
            throw new \InvalidArgumentException('Path cannot be null');
        }

        $this->createDirIfNotExist($path);
        $this->savePrevious($path);
    }

    private function savePrevious(string $path): void
    {
        if (file_exists($path)) {
            if (empty(trim(file_get_contents(filename: $path, length: 10)))) {
                return;
            }

            $prefix = (new \DateTime())->getTimestamp();
            $dirs = explode('/', $path);
            $file = array_pop($dirs);
            $dirs[] = 'rewritten-' . $prefix . '-' . $file;
            $newPath = implode('/', $dirs);

            rename($path, $newPath);
        }
    }

    private function createDirIfNotExist(string $path): void
    {
        $parts = explode('/', $path);
        $dir = implode('/', array_slice($parts, 0, -1));
        if (!is_dir($dir)) {
            mkdir(directory: $dir, recursive: true);
        }
    }
}
