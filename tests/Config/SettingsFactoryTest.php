<?php declare(strict_types=1);

namespace Tests\Glider88\Fixturization\Config;

use Glider88\Fixturization\Config\SettingsFactory;
use Glider88\Fixturization\Config\TableSettings;
use Glider88\Fixturization\Database\Query\WhereFilterClause;
use Glider88\Fixturization\Schema\Schema;
use Glider88\Fixturization\Schema\TableSchema;
use Glider88\Fixturization\Transformer\TransformerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SettingsFactoryTest extends TestCase
{
    public static function createProvider(): array
    {
        $where = new WhereFilterClause('', 'age > 10', new TableSchema('', [], []));

        return [
            'empty case' => [
                [],
                new TableSettings('t1', 1, ['pk1', 'ref_t2', 'col1'], null, []),
            ],
            'filter' => [
                ['filter' => 'age > 10'],
                new TableSettings('t1', 1, ['pk1', 'ref_t2', 'col1'], $where, []),
            ],
            'count' => [
                ['count' => 13],
                new TableSettings('t1', 13, ['pk1', 'ref_t2', 'col1'], null, []),
            ],
            'specific columns' => [
                ['columns' => ['a']],
                new TableSettings('t1', 1, ['a', 'pk1', 'ref_t2'], null, [])
            ],
            'exclude columns' => [
                ['exclude_columns' => ['pk1', 'ref_t2', 'col1']],
                new TableSettings('t1', 1, ['pk1', 'ref_t2'], null, [])
            ],
            'exclude and specific columns' => [
                ['columns' => ['a'], 'exclude_columns' => ['a', 'pk1', 'ref_t2', 'col1']],
                new TableSettings('t1', 1, ['a', 'pk1', 'ref_t2'], null, [])
            ],
        ];
    }

    #[DataProvider('createProvider')]
    public function testCreate(array $settings, TableSettings $expected): void
    {
        $schema = new Schema([
            't1' => [
                'pk' => ['pk1'],
                'columns' => ['pk1', 'ref_t2', 'col1'],
                'foreign_keys' => ['ref_t2' => 't2'],
            ],
            't2' => [
                'pk' => ['pk2'],
                'columns' => ['pk2'],
                'foreign_keys' => [],
            ],
        ]);

        $prevLink = $schema->links('t2', 't1')[0];
        $factory = new SettingsFactory($schema, []);
        $actual = $factory->create('t1', $prevLink, '', $settings);

        $this->assertEquals($expected, $actual);
    }

    public function testTransformers(): void
    {
        $t = $this->createMock(TransformerInterface::class);
        $factory = new SettingsFactory(new Schema(['t' => ['pk' => ['c'], 'columns' => ['c']]]), ['trf' => $t]);
        $actual = $factory->create('t', null, '', ['transformers' => ['c' => ['trf']]]);
        $expected = new TableSettings('t', 1, ['c'], null, ['c' => ['trf' => $t]]);

        $this->assertEquals($expected, $actual);
    }
}
