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
        $fixedSchema = $this->fix($schema);

        $tables = [];
        foreach ($fixedSchema as $tableName => $tableConf) {
            $tables[$tableName] = new TableMeta(
                $tableName,
                (array) $tableConf['pk'],
                $tableConf['columns'] ?? [],
                $tableConf['links'] ?? [],
            );
        }

        return new Schema($tables);
    }

    private function schema(): array
    {
        $fn = static fn(?string $path) => $path === null ? [] : (Yaml::parseFile($path) ?? []);
        $schemaDb = $fn($this->path->schemaDbPath);
        $schemaManual = $fn($this->path->schemaManualPath);

        return $this->merger->merge($schemaDb, $schemaManual);
    }

    private function fix(array $schema): array
    {
        $result = [];
        foreach ($schema as $tableName => $tableConf) {
            $row = $tableConf;
            if (array_key_exists('foreign_keys', $row)) {
                foreach ($tableConf['foreign_keys'] as $fkColumn => $refTable) {
                    $result[$refTable]['links'][$tableName] = $fkColumn;
                }
                unset($row['foreign_keys']);
            }

            if (array_key_exists($tableName, $result)) {
                $result[$tableName] = array_merge($result[$tableName], $row);
            } else {
                $result[$tableName] = $row;
            }
        }

        return $result;
    }
}
