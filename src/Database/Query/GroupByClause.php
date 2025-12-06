<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database\Query;

readonly class GroupByClause
{
    public function __construct(
        public string $alias,

        /** @var list<string> */
        public array $columns,
    ) {}

    public function __toString(): string
    {
        $cols = array_map(fn(string $c) => "$this->alias.$c", $this->columns);

        return implode(", ", $cols);
    }
}
