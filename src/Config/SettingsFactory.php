<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Transformer\TransformerInterface;
use Symfony\Component\Yaml\Yaml;

readonly class ColumnsFactory
{
    /** @param array<TransformerInterface> $transformers */
    public function __construct(
        private Path $path,
        private array $transformers = [],
    ) {}

    public function create(): Columns
    {
        $entrypointConfig = Yaml::parseFile($this->path->configPath) ?? [];
        $columns = $entrypointConfig['column'] ?? [];

        $result = [];
        foreach ($columns as $c) {
            $column = $c['column'];
            $table = $c['table'];
            $transformers = [];
            $transformerNames = $c['transformer'] ?? [];
            foreach ($transformerNames as $name) {
                $transformers[$name] = $this->transformers[$name];
            }

            $result[$table][$column] = new Column($table, $column, $transformers);
        }

        return new Columns($result);
    }
}
