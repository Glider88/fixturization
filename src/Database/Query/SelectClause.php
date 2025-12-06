<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database\Query;

readonly class SelectClause
{
    public function __construct(
        public string $alias,
        public string $column,
    ) {}

    public function __toString(): string
    {
        return "  $this->alias.$this->column {$this->alias}_$this->column";
    }
}
