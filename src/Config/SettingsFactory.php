<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Database\Query\WhereFilterClause;
use Glider88\Fixturization\Schema\Link;
use Glider88\Fixturization\Schema\Schema;
use Glider88\Fixturization\Transformer\TransformerInterface;

readonly class SettingsFactory
{
    public function __construct(
        private Schema $schema,

        /** @var array<string, TransformerInterface> */
        private array $transformersMapper,
    ) {}

    public function create(string $currentTable, ?Link $prevLink, string $alias, array $settings): TableSettings
    {
        $columns = $this->calculateColumns($currentTable, $settings, $prevLink);
        $count = $settings['count'] ?? 1;
        $transformers = $this->chooseTransformers($settings);

        $filter = null;
        if (isset($settings['filter'])) {
            $filter = new WhereFilterClause($alias, $settings['filter'], $this->schema->table($currentTable));
        }

        return new TableSettings(
            name: $currentTable,
            count: $count,
            columns: $columns,
            whereFilter: $filter,
            transformers: $transformers,
        );
    }

    /** @return list<string> */
    private function calculateColumns(string $currentTable, $settings, ?Link $prevLink): array
    {
        $schema = $this->schema->table($currentTable);

        $dbColumns = $schema->cols;
        $columns = $settings['columns'] ?? [];
        $exclude = $settings['exclude_columns'] ?? [];
        $configColumns = $columns ?: array_diff($dbColumns, $exclude);

        $necessary = $schema->pks;
        if ($prevLink) {
            $necessary[] = $prevLink->column;
        }

        return array_unique(array_merge($configColumns, $necessary));
    }

    /** @return list<string, array<string, TransformerInterface>> */
    private function chooseTransformers(array $settings): array
    {
        $transformers = $settings['transformers'] ?? [];

        $result = [];
        foreach ($transformers as $columnName => $names) {
            foreach ($names as $name) {
                $result[$columnName][$name] = $this->transformersMapper[$name];
            }
        }

        return $result;
    }
}
