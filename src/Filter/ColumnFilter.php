<?php declare(strict_types=1);

namespace Glider88\Fixturization\Filter;

use Glider88\Fixturization\Database\WhereClause;

readonly class ColumnFilter implements FilterInterface
{
    public function __construct(
        private string $operator,
        private mixed $value,
    ) {}

    public function filter(string $columnName): WhereClause
    {
        return WhereClause::new($columnName, $this->operator, $this->value);
    }
}
