<?php declare(strict_types=1);

namespace Glider88\Fixturization;

use Glider88\Fixturization\Config\Settings;
use Glider88\Fixturization\Config\Entrypoints;
use Glider88\Fixturization\Config\TableSettings;
use Glider88\Fixturization\Database\DatabaseInterface;
use Glider88\Fixturization\Database\WhereClause;
use Glider88\Fixturization\Schema\Schema;

readonly class Spider
{
    public function __construct(
        private DatabaseInterface $db,
        private Schema            $schema,
        private Settings          $settings,
        private ?int              $seed = null,
    ) {}

    public function start(Entrypoints $entrypoints): array
    {
        $this->setSeed();

        $result = [];
        foreach ($entrypoints->all() as $entryTable => $entrypoint) {
            $tableSchema = $this->schema->table($entryTable);
            $rows = $this->db->randomRows($entryTable, $tableSchema->cols, $entrypoint->count);
            foreach ($rows as $row) {
                $whereClauses = [];
                foreach ($tableSchema->pk as $idCol) {
                    $whereClauses[] = WhereClause::new($idCol, '=', $row[$idCol]);
                }

                foreach ($this->result($entryTable, ...$whereClauses) as $tableName => $idToInfo) {
                    foreach ($idToInfo as $rowId => $res) {
                        $result[$tableName][$rowId] = $res;
                    }
                }
            }
        }

        return $result;
    }

    private function result(string $tableName, WhereClause ...$whereClauses): array
    {
        $tableSchema = $this->schema->table($tableName);
        $tableSettings = $this->settings->tableSettings($tableName);
//        $tableRows = $this->db->row($tableName, $tableSchema->cols, ...$whereClauses);
        $tableRows = $this->fetchRow($tableName, ...$whereClauses);

        if (empty($tableRows)) {
            return [];
        }

        $tableRowRaw = $tableRows[array_rand($tableRows)];
        $tableRow = $this->processRow($tableSettings, $tableRowRaw);

        $nextWhereClauses = [];
        foreach ($tableSchema->pk as $idCol) {
            $nextWhereClauses[] = WhereClause::new($idCol, '=', $tableRow[$idCol]);
        }

        $idsString = implode('|', array_map(static fn(WhereClause $w) => $w->value, $nextWhereClauses));
        $result[$tableName][$idsString] = $tableRow;

        $linkResults = [];
        foreach ($tableSchema->refs as $linkTable => $linkFk) {
//            $linkResults[] = $this->result($linkTable, [$linkFk], $ids);
            $linkResults[] = $this->result($linkTable, ...$nextWhereClauses);
        }

        foreach ($linkResults as $linkResult) {
            foreach ($linkResult as $name => $idToInfo) {
                foreach ($idToInfo as $rowId => $res) {
                    $result[$name][$rowId] = $res;
                }
            }
        }

        return $result;
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
