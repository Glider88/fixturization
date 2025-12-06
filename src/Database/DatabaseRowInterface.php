<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

use Glider88\Fixturization\Database\Query\WhereLinkClause;
use Glider88\Fixturization\Spider\Node;

interface DatabaseRowInterface
{
    /**
     * @param list<WhereLinkClause> $whereClauses
     * @return list<array<string, mixed>>
     */
    public function rows(Node $node, array $whereClauses): array;
}
