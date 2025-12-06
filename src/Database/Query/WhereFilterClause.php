<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database\Query;

use Glider88\Fixturization\Schema\TableSchema;

readonly class WhereFilterClause implements WhereClauseInterface
{
    private string $filterWithAlias;

    public function __construct(
        public string $alias,
        public string $where,
        TableSchema $schema,
    ) {
        $dbColumns = $schema->cols;
        $dbColumnsWithAlias = array_map(fn(string $col) => "$this->alias.$col", $dbColumns);
        $this->filterWithAlias = str_replace($dbColumns, $dbColumnsWithAlias, $this->where);
    }

    public function __toString(): string
    {
        return $this->filterWithAlias;
    }
}
