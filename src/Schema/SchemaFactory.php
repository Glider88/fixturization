<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

use Glider88\Fixturization\Config\Path;
use Symfony\Component\Yaml\Yaml;

readonly class SchemaFactory
{
    public function __construct(
        private Path $path,
        private SchemaMergerInterface $merger,
    ) {}

    public function create(): Schema
    {
        $schema = $this->schema();
        $links = $this->links($schema);

        $tables = [];
        foreach ($schema as $tableName => $tableConf) {
            $tables[$tableName] = new TableMeta(
                $tableName,
                (array) $tableConf['pk'],
                $tableConf['columns'] ?? [],
            );
        }

        return new Schema($tables, $links);
    }

    private function schema(): array
    {
        $fn = static fn(?string $path) => $path === null ? [] : (Yaml::parseFile($path) ?? []);
        $schemaDb = $fn($this->path->schemaDbPath);
        $schemaManual = $fn($this->path->schemaManualPath);

        return $this->merger->merge($schemaDb, $schemaManual);
    }

    private function links(array $schema): array
    {
        $result = [];
        foreach ($schema as $tableName => $tableConf) {
            if (array_key_exists('foreign_keys', $tableConf)) {
                foreach ($tableConf['foreign_keys'] as $fkColumn => $refTable) {
                    $result[$tableName][$refTable] = new Link(
                        LinkType::ManyToOne,
                        $tableName,
                        $fkColumn,
                        $refTable,
                        $schema[$refTable]['pk'],
                    );
                    $result[$refTable][$tableName] = new Link(
                        LinkType::OneToMany,
                        $refTable,
                        $schema[$refTable]['pk'],
                        $tableName,
                        $fkColumn,
                    );
                }
            }
        }

        return $result;
    }
}
