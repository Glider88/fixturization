<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Database\WhereClause;
use Glider88\Fixturization\Transformer\TransformerInterface;

readonly class TableSettings
{
    /**
     * @param array<string, array<TransformerInterface>> $transformers
     * @param array<string> $columns
     */
    public function __construct(
        public int $count,
        public array $columns,
        public ?WhereClause $whereClause,
        private array $transformers,
    ) {}

    /** @return array<TransformerInterface> */
    public function transformers(string $column): array
    {
        return $this->transformers[$column] ?? [];
    }
}
