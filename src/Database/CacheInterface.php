<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

use Glider88\Fixturization\Database\Query\Query;

interface CacheInterface
{
    public function fillCache(Query $query, array $entrypointWheres, array $aliasToNode): void;
}
