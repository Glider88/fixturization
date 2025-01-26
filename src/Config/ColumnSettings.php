<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Transformer\TransformerInterface;

class Column
{
    /** @param array<TransformerInterface> $transformers */
    public function __construct(
        public string $table,
        public string $column,
        public array $transformers = [],
    ) {}
}
