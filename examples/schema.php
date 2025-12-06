<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Glider88\Fixturization\Config\Path;
use Glider88\Fixturization\Database\Postgres\PostgreSQL;
use Glider88\Fixturization\FileGenerator\FileSaver;
use Glider88\Fixturization\Schema\DatabaseSchemaGenerator;
use Psr\Log\AbstractLogger;

const PSQL_SOURCE = 'psql';
const SOURCES = [PSQL_SOURCE];

$source = $argv[1];
if (!in_array($source, SOURCES, true)) {
    $available = implode(', ', SOURCES);
    throw new InvalidArgumentException("Unknown source: $source, available: $available");
}

$baseDir = __DIR__;
$path = Path::newInstance(
    projectDir: $baseDir,
    schemaDbPath: './var/schema/auto-db.yaml',
);

$consoleLogger = new class() extends AbstractLogger {
    public function log($level, Stringable|string $message, array $context = []): void
    {
        echo "$message\n";
    }
};

if ($source === PSQL_SOURCE) {
    $connection = DriverManager::getConnection([
        'dbname'   => 'fixturization',
        'user'     => 'fixturization',
        'password' => 'pass',
        'host'     => 'db',
        'driver'   => 'pdo_pgsql',
        'charset'  => 'utf8',
    ]);

    $psql = new PostgreSQL($connection, $consoleLogger);
    $schemaGenerator = new DatabaseSchemaGenerator($psql);
    $schema = $schemaGenerator->generate();
    $schemaSaver = new FileSaver($path);
    $schemaSaver->saveSchemaAutoDd($schema);
}
