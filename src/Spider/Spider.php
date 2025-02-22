<?php declare(strict_types=1);

namespace Glider88\Fixturization\Spider;

use Glider88\Fixturization\Config\Entrypoint;
use Glider88\Fixturization\Config\Settings;
use Glider88\Fixturization\Config\TableSettings;
use Glider88\Fixturization\Database\DatabaseCached;
use Glider88\Fixturization\Database\DatabaseInterface;
use Glider88\Fixturization\Database\WhereLinkClause;
use Glider88\Fixturization\Schema\Schema;

readonly class Spider
{
    public function __construct(
        private DatabaseInterface $db,
        private DatabaseCached    $cache,
        private Schema            $schema,
        private ?int              $seed = null,
    ) {}

    /**
     * @param array<Entrypoint> $entrypoints
     * @return array<string, array<int|string, array>>
     */
    public function start(array $entrypoints): array
    {
        $this->setSeed();

        $results = [];
        foreach ($entrypoints as $entrypoint) {
            foreach ($entrypoint->roots as $root) {
                $tableSchema = $this->schema->table($root->tableName);
                $rows = $this->fetchRows($root->tableName, $root, $entrypoint->settings, true, []);
                foreach ($rows as $row) {
                    $whereClauses = [];
                    foreach ($tableSchema->pk as $idCol) {
                        $whereClauses[] = new WhereLinkClause($idCol, $row[$idCol]);
                    }

                    $results[] = $this->result($root, $entrypoint->settings, $whereClauses);
                }
            }
        }

        return Result::mergeAll($results)->result();
    }

    /** @param array<WhereLinkClause> $whereClauses */
    private function result(Node $node, Settings $settings, array $whereClauses): Result
    {
        $tableName = $node->tableName;
        $tableSchema = $this->schema->table($tableName);
        $tableSettings = $settings->tableSettings($tableName);
        $tableRowsRaw = $this->fetchRows($tableName, $node, $settings, false, $whereClauses);

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
            $links = $this->schema->links($node->tableName, $child->tableName);
            foreach ($tableRows as $tableRow) {
                foreach ($links as $link) {
                    $nextWhereClauses = new WhereLinkClause($link->linkColumn, $tableRow[$link->ownColumn]);
                    $linkResults[] = $this->result($child, $settings, [$nextWhereClauses]);
                }
            }
        }

        return Result::mergeAll($linkResults);
    }

    private function setSeed(): void
    {
        if ($this->seed !== null) {
            mt_srand($this->seed);
            $this->db->setSeed((float) "0.$this->seed");
        }
    }

    // ToDo: fetchRow, processRow to class?
    /**
     * @param array<WhereLinkClause> $whereClauses
     * @return array<array<string, mixed>>
     */
    private function fetchRows(string $tableName, Node $node, Settings $settings, bool $random, array $whereClauses): array
    {
        if ($node->joinIndex !== null) {
            $this->cache->fetchCache($node->joinIndex, $settings, $random, $whereClauses);
        }

        $tableSettings = $settings->tableSettings($tableName);
        if ($tableSettings->whereFilter !== null) {
            $whereClauses[] = $tableSettings->whereFilter;
        }

        return $this->cache->rows($tableName, $tableSettings->columns, $tableSettings->count, $whereClauses, $random);
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
