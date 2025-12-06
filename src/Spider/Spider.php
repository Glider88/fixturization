<?php declare(strict_types=1);

namespace Glider88\Fixturization\Spider;

use Glider88\Fixturization\Common\Arr;
use Glider88\Fixturization\Common\Traverse;
use Glider88\Fixturization\Config\TableSettings;
use Glider88\Fixturization\Database\CacheInterface;
use Glider88\Fixturization\Database\DatabaseEntrypointInterface;
use Glider88\Fixturization\Database\DatabaseRowInterface;
use Glider88\Fixturization\Database\Query\JoinClause;
use Glider88\Fixturization\Database\Query\Query;
use Glider88\Fixturization\Database\Query\SelectClause;
use Glider88\Fixturization\Database\Query\WhereLinkClause;

readonly class Spider
{
    public function __construct(
        private DatabaseRowInterface | DatabaseEntrypointInterface $db,
        private DatabaseRowInterface | CacheInterface $cache,
    ) {}

    /**
     * @param list<Node> $startNodes
     * @return list<Result>
     */
    public function start(array $startNodes): array
    {
        $results = [];
        foreach ($startNodes as $node) {
            $aliasToNode = $this->aliasToNode($node);
            $query = $this->entrypointQuery($node);
            $entrypoints = $this->db->entrypointIds($node, $query);
            foreach ($entrypoints as $row) {
                $whereClauses = [];
                foreach ($row as $idCol => $idVal) {
                    $whereClauses[] = new WhereLinkClause($node->alias, $idCol, $idVal);
                }

                $this->cache->fillCache($query, $whereClauses, $aliasToNode);
                $results[] = $this->resultRec($node, $whereClauses, $aliasToNode);
            }
        }

        return $results;
    }

    /** @param array<WhereLinkClause> $whereClauses */
    private function resultRec(Node $node, array $whereClauses, array $aliasToNode): Result
    {
        $tableSchema = $node->schema;
        $tableSettings = $node->settings;

        $tableRowsRaw = $this->cache->rows($node, $whereClauses);
        if (empty($tableRowsRaw)) {
            return Result::newEmpty();
        }

        $tableRows = [];
        foreach ($tableRowsRaw as $tableRowRaw) {
            $tableRows[] = $this->processRow($tableSettings, $tableRowRaw);
        }

        if (empty($node->children)) {
            return Result::new($tableSchema, $tableRows);
        }

        $linkResults = [Result::new($tableSchema, $tableRows)];
        foreach ($node->children as $child) {
            foreach ($tableRows as $tableRow) {
                $link = $child->link;
                $nextWhereClauses = new WhereLinkClause($child->alias, $link->column, $tableRow[$link->parentColumn]);
                $linkResults[] = $this->resultRec($child, [$nextWhereClauses], $aliasToNode);
            }
        }

        return Result::mergeAll($linkResults);
    }

    private function entrypointQuery(Node $start): Query
    {
        $selects = [];
        $wheres = [];
        $passedNodes = [];
        $joins = [];
        foreach (Traverse::travelPath($start) as $path) {
            $joinPath = $this->pathWithJoins($path);
            $prev = null;
            foreach ($joinPath as $node) {
                if (array_key_exists($node->alias, $passedNodes)) {
                    $prev = $node;
                    continue;
                }
                $passedNodes[$node->alias] = true;

                foreach ($node->settings->columns as $column) {
                    $selects[] = new SelectClause($node->alias, $column);
                }

                if ($prev) {
                    $joins[] = JoinClause::make($prev, $node);
                }

                if ($node->settings->whereFilter) {
                    $wheres[] = $node->settings->whereFilter;
                }

                $prev = $node;
            }
        }

        return new Query(
            select: $selects,
            table: $start->name,
            alias: $start->alias,
            joins: $joins,
            wheres: $wheres,
            limit: $start->settings->count,
        );
    }

    /** @return array<string, Node> */
    private function aliasToNode(Node $start): array
    {
        $aliasToNode = [];
        foreach (Traverse::travelDeep($start) as $node) {
            $aliasToNode[$node->alias] = $node;
        }

        return $aliasToNode;
    }

    /**
     * @param list<Node> $path
     * @return list<Node>
     */
    private function pathWithJoins(array $path): array
    {
        $lastFilterIndex = null;
        foreach ($path as $i => $node) {
            if ($node->settings->whereFilter) {
                $lastFilterIndex = $i;
            }
        }

        if ($lastFilterIndex === null) {
            return [];
        }

        return Arr::sliceFirst($path, $lastFilterIndex + 1);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function processRow(TableSettings $tableSettings, array $row): array
    {
        foreach ($row as $column => $value) {
            $transformers = $tableSettings->transformers($column);
            foreach ($transformers as $transformer) {
                $row[$column] = $transformer->transform($row[$column]);
            }
        }

        return $row;
    }
}
