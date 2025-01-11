<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Glider88\Fixturization\Config\EntrypointsFactory;
use Glider88\Fixturization\Config\Path;
use Glider88\Fixturization\Database\PostgreSQL;
use Glider88\Fixturization\FileGenerator\FileSaver;
use Glider88\Fixturization\FileGenerator\PostgresSqlTransformer;
use Glider88\Fixturization\Schema\SchemaFactory;
use Glider88\Fixturization\Schema\SchemaMerger;
use Glider88\Fixturization\Spider;
use Doctrine\DBAL\DriverManager;

const SQL_TARGET = 'sql';
const YAML_TARGET = 'yaml';
const TARGETS = [SQL_TARGET, YAML_TARGET];

$targets = array_splice($argv, 1);
foreach ($targets as $target) {
    if (!in_array($target, TARGETS, true)) {
        $available = implode(', ', TARGETS);
        throw new \InvalidArgumentException("Unknown target: $target, available: $available");
    }
}

$baseDir = __DIR__;

$path = Path::newInstance(
    projectDir: $baseDir,
    entrypointPath: './config/entrypoint.yaml',
    fixtureYamlPath: './var/fixture/data.yaml',
    fixtureSqlPath: './var/fixture/data.sql',
    schemaDbPath: './var/schema/auto-db.yaml',
    schemaManualPath: './var/schema/manual.yaml',
);

$connection = DriverManager::getConnection([
    'dbname'   => 'fixturization',
    'user'     => 'fixturization',
    'password' => 'pass',
    'host'     => 'db',
    'driver'   => 'pdo_pgsql',
    'charset'  => 'utf8',
]);

$psql = new PostgreSQL($connection);
$schemaFactory = new SchemaFactory($path, new SchemaMerger());
$schema = $schemaFactory->create();
$entrypointFactory = new EntrypointsFactory($path);
$entrypoints = $entrypointFactory->create();
$spider = new Spider($psql, $schema, 42);
$result = $spider->start($entrypoints);

$fixtureSaver = new FileSaver($path);
if (in_array(SQL_TARGET, $targets, true)) {
    $sql = (new PostgresSqlTransformer())->sql($result);
    $fixtureSaver->saveFixtureSql($sql);
}

if (in_array(YAML_TARGET, $targets, true)) {
    $fixtureSaver->saveFixtureYaml($result);
}
