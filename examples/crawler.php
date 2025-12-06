<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Glider88\Fixturization\Common\Yaml;
use Glider88\Fixturization\Config\Path;
use Glider88\Fixturization\Config\SettingsFactory;
use Glider88\Fixturization\Config\SettingsMerger;
use Glider88\Fixturization\Database\DatabaseCached;
use Glider88\Fixturization\Database\Postgres\PostgreSQL;
use Glider88\Fixturization\FileGenerator\FileSaver;
use Glider88\Fixturization\FileGenerator\PostgresSqlTransformer;
use Glider88\Fixturization\Schema\Schema;
use Glider88\Fixturization\Schema\SchemaMerger;
use Glider88\Fixturization\Spider\NodeFactory;
use Glider88\Fixturization\Spider\Result;
use Glider88\Fixturization\Spider\Spider;
use Glider88\Fixturization\Transformer\ColumnShuffle;
use Psr\Log\AbstractLogger;

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

$consoleLogger = new class() extends AbstractLogger {
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        echo "$message\n";
    }
};

$psql = new PostgreSQL($connection, $consoleLogger, dryRun: false, random: true, seed: 0.42);

$schemaDb = Yaml::parse($path->schemaDbPath);
$schemaManual = Yaml::parse($path->schemaManualPath);
$schemaConf = SchemaMerger::merge($schemaDb, $schemaManual);

$schema = new Schema($schemaConf);

$transformersMapper = [
    'column_shuffle' => new ColumnShuffle(),
];

$settingsMerger = new SettingsMerger($schema->allTables());
$config = Yaml::parse($path->configPath);
$entrypoints = $settingsMerger->enrichSettings($config);

$settingsFactory = new SettingsFactory($schema, $transformersMapper);
$nodeFactory = new NodeFactory($schema, $settingsFactory);

$nodes = [];
foreach ($entrypoints as $entrypointConf) {
    $nodes[] = $nodeFactory->create($entrypointConf);
}

$dbCached = new DatabaseCached($psql);

$spider = new Spider($psql, $dbCached);
$results = $spider->start($nodes);
// $results += Result::new(additional data from custom sql's)
$result = Result::mergeAll($results);

$fixtureSaver = new FileSaver($path);
if (in_array(SQL_TARGET, $targets, true)) {
    $sql = PostgresSqlTransformer::sql($result);
    $fixtureSaver->saveFixtureSql($sql);
}

if (in_array(YAML_TARGET, $targets, true)) {
    $fixtureSaver->saveFixtureYaml($result);
}
