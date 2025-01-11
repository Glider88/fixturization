<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Glider88\Fixturization\Config\Path;
use Glider88\Fixturization\Database\PostgreSQL;
use Glider88\Fixturization\FileGenerator\FileSaver;
use Glider88\Fixturization\Schema\DatabaseSchemaGenerator;
use Doctrine\DBAL\DriverManager;

const PSQL_SOURCE = 'psql';
const SOURCES = [PSQL_SOURCE];

$source = $argv[1];
if (!in_array($source, SOURCES, true)) {
    $available = implode(', ', SOURCES);
    throw new \InvalidArgumentException("Unknown source: $source, available: $available");
}

$baseDir = __DIR__;
$path = Path::newInstance(
    projectDir: $baseDir,
    schemaDbPath: './var/schema/auto-db.yaml',
);

if ($source === PSQL_SOURCE) {
    $connection = DriverManager::getConnection([
        'dbname'   => 'fixturization',
        'user'     => 'fixturization',
        'password' => 'pass',
        'host'     => 'db',
        'driver'   => 'pdo_pgsql',
        'charset'  => 'utf8',
    ]);

    $psql = new PostgreSQL($connection);
    $schemaGenerator = new DatabaseSchemaGenerator($psql);
    $schema = $schemaGenerator->generate();
    $schemaSaver = new FileSaver($path);
    $schemaSaver->saveSchemaAutoDd($schema);
}
