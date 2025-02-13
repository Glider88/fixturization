<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Filter\FilterInterface;
use Glider88\Fixturization\Transformer\TransformerInterface;

readonly class SettingsFactory
{
    /** @param array<string, TransformerInterface> $transformersMapper */
    /** @param array<string, FilterInterface> $filtersMapper */
    public function __construct(
        private array $transformersMapper = [],
        private array $filtersMapper = [],
    ) {}

    public function create(array $config): Settings
    {
        $tables = $config['tables'] ?? [];

        $settings = [];
        foreach ($tables as $tableName => $tableConfig) {
            $columns = $tableConfig['columns'] ?? [];
            $columnSettings = [];
            foreach ($columns as $columnName => $columnConfig) {
                $transformerNames = $columnConfig['transformers'] ?? [];
                $filtersNames = $columnConfig['filters'] ?? [];

                $transformers = [];
                foreach ($transformerNames as $name) {
                    $transformers[$name] = $this->transformersMapper[$name];
                }

                $filters = [];
                foreach ($filtersNames as $name) {
                    $filters[$name] = $this->filtersMapper[$name];
                }

                $columnSettings[$columnName] = new ColumnSettings($tableName, $columnName, $transformers, $filters);
            }

            $settings[$tableName] = new TableSettings(
                $columnSettings,
                $tableConfig['count'] ?? null,
            );
        }

        return new Settings($settings);
    }
}
