<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

use Glider88\Fixturization\Common\Arr;
use LogicException;

readonly class Schema
{
    /** @var array<string, TableSchema> */
    private array $tableToSchema;

    /** @var array<string, array<string, array<Link>>> */
    private array $links;

    public function __construct(array $schema) {
        $tableToSchema = [];
        foreach ($schema as $conf) {
            $tableToSchema[$conf['name']] = new TableSchema($conf['name'], $conf['pk'], $conf['columns']);
        }
        $this->tableToSchema = $tableToSchema;

        $links = [];
        foreach ($schema as $tableName => $tableConf) {
            if (array_key_exists('foreign_keys', $tableConf)) {
                foreach ($tableConf['foreign_keys'] as $fkColumn => $refTable) {
                    if (count($schema[$refTable]['pk']) > 1) {
                        throw new LogicException("Multiple primary keys for table: $refTable, reference to $tableName");
                    }

                    $refPk = Arr::first($schema[$refTable]['pk']);

                    $links[$tableName][$refTable][] = new Link(
                        LinkType::ManyToOne,
                        $tableName,
                        $fkColumn,
                        $refTable,
                        $refPk,
                    );
                    $links[$refTable][$tableName][] = new Link(
                        LinkType::OneToMany,
                        $refTable,
                        $refPk,
                        $tableName,
                        $fkColumn,
                    );
                }
            }
        }
        $this->links = $links;
    }

    public function table(string $tableName): TableSchema
    {
        if (! array_key_exists($tableName, $this->tableToSchema)) {
            throw new \InvalidArgumentException("Table '$tableName' does not exist in Schema");
        }

        return $this->tableToSchema[$tableName];
    }

    /** @return list<string> */
    public function allTables(): array
    {
        return array_keys($this->tableToSchema);
    }

    /** @return list<Link> */
    public function links(string $tableFrom, string $tableTo): array
    {
        return $this->links[$tableFrom][$tableTo] ?? [];
    }
}
