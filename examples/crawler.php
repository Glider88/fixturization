<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Glider88\Fixturization\Config\EntrypointsFactory;
use Glider88\Fixturization\Config\Path;
use Glider88\Fixturization\Config\SettingsFactory;
use Glider88\Fixturization\Config\SettingsMerger;
use Glider88\Fixturization\Database\DatabaseCached;
use Glider88\Fixturization\Database\Cache;
use Glider88\Fixturization\Database\PostgreSQL;
use Glider88\Fixturization\FileGenerator\FileSaver;
use Glider88\Fixturization\FileGenerator\PostgresSqlTransformer;
use Glider88\Fixturization\Schema\SchemaFactory;
use Glider88\Fixturization\Schema\SchemaMerger;
use Glider88\Fixturization\Spider\Spider;
use Glider88\Fixturization\Transformer\ColumnShuffle;
use Symfony\Component\Yaml\Yaml;

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
    configPath: './config/config.yaml',
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

$parseFn = static fn(?string $p) => $p === null ? [] : (Yaml::parseFile($p) ?? []);
$schemaFactory = new SchemaFactory(
    $parseFn($path->schemaDbPath),
    $parseFn($path->schemaManualPath),
    new SchemaMerger(),
);
$schema = $schemaFactory->create();

$transformersMapper = [
    'column_shuffle' => new ColumnShuffle(),
];
$settingsFactory = new SettingsFactory($schema, $transformersMapper);
$settingsMerger = new SettingsMerger($schema);
$config = Yaml::parseFile($path->configPath) ?? [];
$entrypointFactory = new EntrypointsFactory($config, $settingsMerger, $settingsFactory);
$entrypoints = $entrypointFactory->create();

$cache = new Cache($connection, $schema, );
$dbCached = new DatabaseCached($psql, $cache);

$spider = new Spider($psql, $dbCached, $schema, 42);
$result = $spider->start($entrypoints);

$fixtureSaver = new FileSaver($path);
if (in_array(SQL_TARGET, $targets, true)) {
    $sql = (new PostgresSqlTransformer())->sql($result);
    $fixtureSaver->saveFixtureSql($sql);
}

if (in_array(YAML_TARGET, $targets, true)) {
    $fixtureSaver->saveFixtureYaml($result);
}
