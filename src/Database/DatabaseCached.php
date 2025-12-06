<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

use Glider88\Fixturization\Database\Query\Query;
use Glider88\Fixturization\Database\Query\WhereLinkClause;
use Glider88\Fixturization\Spider\Node;
use Glider88\Fixturization\Spider\Result;

class DatabaseCached implements DatabaseRowInterface, CacheInterface
{
    /** @var array<string, array<int|string, array<string, mixed>>> */
    private array $aliasToIdToRow = [];

    public function __construct(
        readonly private DatabaseRowInterface | DatabaseEntrypointInterface $db,
    ) {}

    /**
     * @param list<WhereLinkClause> $entrypointWheres
     * @param array<string, Node> $aliasToNode
     */
    public function fillCache(Query $query, array $entrypointWheres, array $aliasToNode): void
    {
        $rawRows = $this->db->entrypointCache($query, $entrypointWheres);
        foreach ($rawRows as $alias => $rows) {
            foreach ($rows as $row) {
                $pks = $aliasToNode[$alias]->schema->pks;
                $ids = [];
                foreach ($pks as $pk) {
                    $ids[] = $row[$pk];
                }

                $ids = implode(Result::MULTI_PK_SEPARATOR, $ids);
                $this->aliasToIdToRow[$alias][$ids] = $row;
            }
        }
    }

    /** @inheritDoc */
    public function rows(Node $node, array $whereClauses): array
    {
        $cache = $this->fetchFromCache($node, $whereClauses);
        if ($cache) {
            return $cache;
        }

        return $this->db->rows($node, $whereClauses);
    }

    /**
     * @param list<WhereLinkClause> $whereClauses
     * @return array<array<string, mixed>>
     */
    private function fetchFromCache(Node $node, array $whereClauses): array
    {
        $rows = $this->aliasToIdToRow[$node->alias] ?? [];

        $filterFn =
            static fn(WhereLinkClause $where) =>
                static fn(array $row) =>
                    array_key_exists($where->column, $row) && $row[$where->column] === $where->value;

        foreach ($whereClauses as $where) {
            $rows = array_filter($rows, $filterFn($where));
        }

        $columnAsKeys = array_flip($node->settings->columns);
        $result = array_map(static fn(array $r) => array_intersect_key($r, $columnAsKeys), $rows);

        return array_slice($result, 0, $node->settings->count);
    }
}
