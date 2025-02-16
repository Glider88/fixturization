<?php declare(strict_types=1);

namespace Glider88\Fixturization\Spider;

use Glider88\Fixturization\Config\Entrypoint;
use Glider88\Fixturization\Config\Settings;
use Glider88\Fixturization\Config\TableSettings;
use Glider88\Fixturization\Database\DatabaseInterface;
use Glider88\Fixturization\Database\WhereClause;
use Glider88\Fixturization\Schema\Schema;

readonly class Spider
{
    public function __construct(
        private DatabaseInterface $db,
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
                $entrypointSettings = $entrypoint->settings;
                $entrypointCount = $entrypointSettings->tableSettings($root->tableName)->count;
                $rows = $this->db->randomRows($root->tableName, $tableSchema->cols, $entrypointCount);
                foreach ($rows as $row) {
                    $whereClauses = [];
                    foreach ($tableSchema->pk as $idCol) {
//                        $whereClauses[] = WhereClause::new($idCol, '=', $row[$idCol]);
//                        $whereClauses[] = WhereClause::new($idCol, '=', 625);
                        $whereClauses[] = WhereClause::new($idCol, '=', 1);
//                        $whereClauses[] = WhereClause::new($idCol, '=', 44);
                    }

                    $results[] = $this->result($root, $entrypointSettings, ...$whereClauses);
                }
            }
        }

        return Result::mergeAll($results)->result();
    }

    private function result(Node $node, Settings $settings, WhereClause ...$whereClauses): Result
    {
        $tableName = $node->tableName;
        $tableSchema = $this->schema->table($tableName);
        $tableSettings = $settings->tableSettings($tableName);
        $tableRowsRaw = $this->fetchRow($tableName, $settings, ...$whereClauses);

        if (empty($tableRowsRaw)) {
            return Result::newEmpty();
        }

        $tableRows = [];
        foreach ($tableRowsRaw as $tableRowRaw) {
            $tableRows[] = $this->processRow($tableSettings, $tableRowRaw);
        }

        if (empty($node->children)) {
            return Result::new(Status::Done, $tableSchema, $tableRows);
        }

        $linkResults = [Result::new(Status::Done, $tableSchema, $tableRows)];
        foreach ($node->children as $child) {
            $links = $this->schema->links($node->tableName, $child->tableName);
            foreach ($tableRows as $tableRow) {
                foreach ($links as $link) {
                    $nextWhereClauses = WhereClause::new($link->linkColumn, '=', $tableRow[$link->ownColumn]);
                    $linkResults[] = $this->result($child, $settings, $nextWhereClauses);
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

    private function fetchRow(string $tableName, Settings $settings, WhereClause ...$whereClauses): array
    {
        $tableSettings = $settings->tableSettings($tableName);
        if ($tableSettings->whereClause !== null) {
            $whereClauses[] = $tableSettings->whereClause;
        }

        return $this->db->row($tableName, $tableSettings->columns, $tableSettings->count, ...$whereClauses);
    }

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
