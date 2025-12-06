<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database\Query;

use Glider88\Fixturization\Common\Sql;

readonly class WhereLinkClause implements WhereClauseInterface
{
    public function __construct(
        public string $alias,
        public string $column,
        public mixed $value,
    ) {}

    public function __toString(): string
    {
        [$operator, $value] = Sql::fixOpVal($this->value);

        return "$this->alias.$this->column $operator $value";
    }
}
