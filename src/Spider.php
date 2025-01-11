<?php declare(strict_types=1);

namespace Glider88\Fixturization;

use Glider88\Fixturization\Config\Entrypoints;
use Glider88\Fixturization\Database\DatabaseInterface;
use Glider88\Fixturization\Schema\Schema;

readonly class Spider
{
    public function __construct(
        private DatabaseInterface $db,
        private Schema $schema,
        private ?int $seed = null,
    ) {}

    public function start(Entrypoints $entrypoints): array
    {
        $this->setSeed();

        $result = [];
        foreach ($entrypoints->all() as $entryTable => $entrypoint) {
            $table = $this->schema->table($entryTable);
            $rows = $this->db->randomRows($entryTable, $table->cols, $entrypoint->count);
            foreach ($rows as $row) {
                $ids = array_map(static fn(string $idCol) => $row[$idCol], $table->pk);
                foreach ($this->result($entryTable, $table->pk, $ids) as $tableName => $idToInfo) {
                    foreach ($idToInfo as $rowId => $res) {
                        $result[$tableName][$rowId] = $res;
                    }
                }
            }
        }

        return $result;
    }

    private function result(string $tableName, array $whereColumns, array $whereValues): array
    {
        $table = $this->schema->table($tableName);
        $tableRows = $this->db->row($tableName, $table->cols, $whereColumns, $whereValues);
        if (empty($tableRows)) {
            return [];
        }

        $tableRow = $tableRows[array_rand($tableRows)];
        $ids = [];
        foreach ($table->pk as $idCol) {
            $ids[] = $tableRow[$idCol];
        }
        $idsString = implode('|', $ids);
        $result[$tableName][$idsString] = $tableRow;

        $linkResults = [];
        foreach ($table->refs as $linkTable => $linkFk) {
            $linkResults[] = $this->result($linkTable, [$linkFk], $ids);
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
        mt_srand($this->seed);
        $this->db->setSeed((float) "0.$this->seed");
    }
}
