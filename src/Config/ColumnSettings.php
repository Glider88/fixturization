<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Filter\FilterInterface;
use Glider88\Fixturization\Transformer\TransformerInterface;

class ColumnSettings
{
    /** @param array<TransformerInterface> $transformers */
    /** @param array<FilterInterface> $filters */
    public function __construct(
        public string $table,
        public string $column,
        public array $transformers = [],
        public array $filters = [],
    ) {}
}
