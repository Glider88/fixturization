<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database\Query;

readonly class Query
{
    public function __construct(
        /** @var list<SelectClause> */
        public array  $select,
        public string $table,
        public string $alias,

        /** @var list<JoinClause> */
        public array  $joins = [],

        /** @var WhereClauseInterface[] */
        public array  $wheres = [],
        public int    $limit = 1,
    ) {}
}
