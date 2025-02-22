<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

readonly class WhereFilterClause implements WhereClauseInterface
{
    public function __construct(
        public string $where,
    ) {}

    public function __toString(): string
    {
        return $this->where;
    }
}
