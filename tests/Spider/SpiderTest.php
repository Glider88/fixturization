<?php declare(strict_types=1);

namespace Tests\Glider88\Fixturization\Spider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Glider88\Fixturization\Common\Arr;
use Glider88\Fixturization\Config\SettingsFactory;
use Glider88\Fixturization\Database\DatabaseCached;
use Glider88\Fixturization\Database\Postgres\PostgreSQL;
use Glider88\Fixturization\Schema\Schema;
use Glider88\Fixturization\Spider\NodeFactory;
use Glider88\Fixturization\Spider\Result;
use Glider88\Fixturization\Spider\Spider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\Yaml\Yaml;

class SpiderTest extends TestCase
{
    private static Connection $connection;

    public static function setUpBeforeClass(): void
    {
        self::$connection = DriverManager::getConnection([
            'dbname'   => 'fixturization',
            'user'     => 'fixturization',
            'password' => 'pass',
            'host'     => 'db',
            'driver'   => 'pdo_pgsql',
            'charset'  => 'utf8',
        ]);

        self::$connection->executeStatement(<<<SQL
drop table if exists t1 cascade;
drop table if exists t2 cascade;
drop table if exists t3 cascade;
drop table if exists t4 cascade;
drop table if exists before_tree cascade;
drop table if exists tree cascade;
SQL
        );

        self::$connection->executeStatement(<<<SQL
create table if not exists t1 (
  id int primary key,
  col text not null
);
create table if not exists t4 (
  id int primary key,
  col text not null
);
create table if not exists t3 (
  id int primary key,
  t4_id int not null references t4(id),
  col text not null
);
create table if not exists t2 (
  id int primary key,
  t1_id int not null references t1(id),
  t3_id int not null references t3(id),
  col text not null
);

create table if not exists tree (
  id serial primary key,
  parent_id int references tree(id),
  col text not null
);

create table if not exists before_tree (
  id serial primary key,
  tree_id int references tree(id)
);

insert into t1(id, col) values 
(1, 'one'),
(2, 'two');

insert into t4(id, col) values 
(1, 'right'),
(2, 'left');

insert into t3(id, t4_id, col) values 
(1, 1, 'cat'),
(2, 2, 'dog');

insert into t2(id, t1_id, t3_id, col) values
(1, 1, 1, 'blue'),
(2, 1, 2, 'red'),
(3, 2, 1, 'yellow'),
(4, 2, 2, 'green');
SQL
        );

        self::$connection->insert('tree', ['parent_id' => null, 'col' => 'root']);
        $id = (int) self::$connection->lastInsertId();

        self::fillTree($id, 'A', 9);
        self::fillTree($id, 'B', 9);

        $middleTreeId = self::$connection->fetchOne('select id from tree where col = ?', ['AAAAA']);
        self::$connection->insert('before_tree', ['tree_id' => $middleTreeId]);
    }

    public static function startProvider(): array
    {
        $baseEntrypoint = [
            'start' => 't1',
            't1' => ['filter' => "col = 'two'"],
            't2' => ['filter' => "col = 'yellow'"],
            't3' => ['filter' => "col = 'cat'"],
        ];

        $baseExpected = [
            't1' => [2 => ['id' => 2, 'col' => 'two']],
            't2' => [3 => ['id' => 3, 't1_id' => 2, 't3_id' => 1, 'col' => 'yellow']],
            't3' => [1 => ['id' => 1, 't4_id' => 1, 'col' => 'cat']],
            't4' => [1 => ['id' => 1, 'col' => 'right']],
        ];

        $treeEntrypoint = [
            'start' => 'before_tree',
            'tree' => ['tree' => 2],
            'route-settings' => [[
                'route' => ['tree', 'tree', 'tree'],
            ]],
        ];

        $treeExpected = [
            'before_tree' => [1 => ['id' => 1, 'tree_id' => 6]],
            'tree' => [
                6 => ['id' => 6, 'parent_id' => 5, 'col' => 'AAAAA'],
                5 => ['id' => 5, 'parent_id' => 4, 'col' => 'AAAA'],
                4 => ['id' => 4, 'parent_id' => 3, 'col' => 'AAA'],
                37 => ['id' => 37, 'parent_id' => 5, 'col' => 'AAAAB'],
                7 => ['id' => 7, 'parent_id' => 6, 'col' => 'AAAAAA'],
                15 => ['id' => 15, 'parent_id' => 7, 'col' => 'AAAAAAB'],
            ],
        ];

        return [
            [$baseEntrypoint, $baseExpected],
            [$treeEntrypoint, $treeExpected],
        ];
    }

    #[DataProvider('startProvider')]
    public function testStart(array $entrypoint, array $expected): void
    {
        $nullLogger = new class() extends AbstractLogger {
            public function log($level, \Stringable|string $message, array $context = []): void {}
        };

        $psql = new PostgreSQL(self::$connection, $nullLogger, dryRun: false, random: true, seed: 0.42);
        $dbCached = new DatabaseCached($psql);

        $schemaConf = Yaml::parse(<<<YML
t4:
  pk: [id]
  columns: [id, col]
t3:
  pk: [id]
  columns: [id, t4_id, col]
  foreign_keys:
    t4_id: t4
t1:
  pk: [id]
  columns: [id, col]
t2:
  pk: [id]
  columns: [id, t1_id, t3_id, col]
  foreign_keys:
    t1_id: t1
    t3_id: t3
before_tree:
  pk: [id]
  columns: [id, tree_id]
  foreign_keys:
    tree_id: tree
tree:
  pk: [id]
  columns: [id, parent_id, col]
  foreign_keys:
    parent_id: tree
YML
        );

        $schema = new Schema($schemaConf);
        $settingsFactory = new SettingsFactory($schema, []);
        $nodeFactory = new NodeFactory($schema, $settingsFactory);
        $nodes = $nodeFactory->create($entrypoint);

        $spider = new Spider($psql, $dbCached);

        /** @var list<Result> $results */
        $results = $spider->start([$nodes]);
        $result = Arr::first($results);
        $actual = $result->result();

        $this->assertSame($expected, $actual);
    }

    private static function fillTree(int $parentId, string $col, int $depth): void
    {
        if ($depth <= 0) {
            return;
        }

        self::$connection->insert('tree', [
            'parent_id' => $parentId,
            'col' => $col
        ]);

        $id = (int) self::$connection->lastInsertId();

        self::fillTree($id, $col . 'A', $depth - 1);
        self::fillTree($id, $col . 'B', $depth - 1);
    }

    public static function tearDownAfterClass(): void
    {
        self::$connection->executeStatement(<<<SQL
drop table if exists t1 cascade;
drop table if exists t2 cascade;
drop table if exists t3 cascade;
drop table if exists t4 cascade;
drop table if exists before_tree cascade;
drop table if exists tree cascade;
SQL
        );
    }
}
