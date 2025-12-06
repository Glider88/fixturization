<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

use Glider88\Fixturization\Database\Query\Query;
use Glider88\Fixturization\Database\Query\WhereLinkClause;
use Glider88\Fixturization\Spider\Node;

interface DatabaseEntrypointInterface
{
    /** @return list<array<string, mixed>> */
    public function entrypointIds(Node $start, Query $query): array;

    /** @param list<WhereLinkClause> $entrypointWheres */
    public function entrypointCache(Query $query, array $entrypointWheres = []): array;
}
