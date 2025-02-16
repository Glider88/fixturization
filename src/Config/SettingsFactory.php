<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Database\WhereClause;
use Glider88\Fixturization\Schema\Schema;
use Glider88\Fixturization\Transformer\TransformerInterface;

readonly class SettingsFactory
{
    /** @param array<string, TransformerInterface> $transformersMapper */
    public function __construct(
        private Schema $schema,
        private array $transformersMapper = [],
    ) {}

    public function create(array $config): Settings
    {
        $settings = [];
        foreach ($config['settings']['tables'] as $tableName => $tableConfig) {
            $tableSchema = $this->schema->table($tableName);

            $whereClause = null;
            if (!empty($tableConfig['filter'])) {
                $whereClause = new WhereClause($tableConfig['filter']);
            }

            $transformers = [];
            foreach ($tableConfig['transformers'] as $columnName => $names) {
                $transformers[$columnName] = array_intersect_key(
                    $this->transformersMapper,
                    array_flip($names),
                );
            }

            $cols = $tableConfig['columns'] ?? $tableSchema->cols;
            $settings[$tableName] = new TableSettings($tableConfig['count'], $cols, $whereClause, $transformers);
        }

        return new Settings($settings);
    }
}
