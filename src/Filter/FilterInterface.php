<?php declare(strict_types=1);

namespace Glider88\Fixturization\Filter;

use Glider88\Fixturization\Database\WhereClause;

interface FilterInterface
{
    public function filter(string $columnName): WhereClause;
}
