<?php declare(strict_types=1);

namespace Glider88\Fixturization\Spider;

use Glider88\Fixturization\Config\Entrypoint;
use Glider88\Fixturization\Config\Settings;
use Glider88\Fixturization\Config\TableSettings;
use Glider88\Fixturization\Database\DatabaseInterface;
use Glider88\Fixturization\Database\WhereClause;
use Glider88\Fixturization\Schema\Schema;

// ToDo: film 2*-> language
readonly class Spider
{
    public function __construct(
        private DatabaseInterface $db,
        private Schema            $schema,
        private Settings          $settings,
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
                $rows = $this->db->randomRows($root->tableName, $tableSchema->cols, $entrypoint->count);
                foreach ($rows as $row) {
                    $whereClauses = [];
                    foreach ($tableSchema->pk as $idCol) {
                        $whereClauses[] = WhereClause::new($idCol, '=', $row[$idCol]);
                    }

                    $results[] = $this->result($root, ...$whereClauses);
                }
            }
        }

        return Result::mergeAll($results)->result();
    }

    private function result(Node $node, WhereClause ...$whereClauses): Result
    {
        $tableName = $node->tableName;
        $tableSchema = $this->schema->table($tableName);
        $tableSettings = $this->settings->tableSettings($tableName);
        $tableRows = $this->fetchRow($tableName, ...$whereClauses);

        if (empty($tableRows)) {
            return Result::newEmpty();
        }

        $tableRowRaw = $tableRows[array_rand($tableRows)];
        $tableRow = $this->processRow($tableSettings, $tableRowRaw);

        if (empty($node->children)) {
            return Result::new(Status::Done, $tableSchema, [$tableRow]);
        }

        $linkResults = [Result::new(Status::Done, $tableSchema, [$tableRow])];
        foreach ($node->children as $child) {
            $link = $this->schema->link($node->tableName, $child->tableName);
            $nextWhereClauses = WhereClause::new($link->linkColumn, '=', $tableRow[$link->ownColumn]);
            $linkResults[] = $this->result($child, $nextWhereClauses);
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

    private function fetchRow(string $tableName, WhereClause ...$whereClauses): array
    {
        $tableSchema = $this->schema->table($tableName);
        $allColumnSettings = $this->settings->tableSettings($tableName)?->all() ?: [];

        $filterWheres = [];
        foreach ($allColumnSettings as $columnName => $columnSettings) {
            foreach ($columnSettings->filters as $filter) {
                $filterWheres[] = $filter->filter($columnName);
            }
        }

        $allWheres = array_merge($filterWheres, $whereClauses);

        return $this->db->row($tableName, $tableSchema->cols, ...$allWheres);
    }

    private function processRow(?TableSettings $tableSettings, array $row): array
    {
        $newRow = [];
        foreach ($row as $column => $value) {
            $transformers = $tableSettings?->columnSettings($column)?->transformers;
            if ($transformers === null) {
                $newRow[$column] = $value;
                continue;
            }

            $newValue = $value;
            foreach ($transformers as $transformer) {
                $newValue = $transformer->transform($newValue);
            }
            $newRow[$column] = $newValue;
        }

        return $newRow;
    }
}
