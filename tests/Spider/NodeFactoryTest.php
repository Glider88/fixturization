<?php declare(strict_types=1);

namespace Tests\Glider88\Fixturization\Spider;

use Glider88\Fixturization\Common\Arr;
use Glider88\Fixturization\Common\Traverse;
use Glider88\Fixturization\Config\SettingsFactory;
use Glider88\Fixturization\Schema\Schema;
use Glider88\Fixturization\Spider\NodeFactory;
use Glider88\Fixturization\Transformer\TransformerInterface;
use PHPUnit\Framework\TestCase;

class NodeFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $schema = new Schema([
            't1' => [
                'pk' => ['pk1'],
                'columns' => ['pk1', 'ref_t2', 'col1'],
                'foreign_keys' => ['ref_t2' => 't2'],
            ],
            't2' => [
                'pk' => ['pk2'],
                'columns' => ['pk2', 'ref_t3', 'col2'],
                'foreign_keys' => ['ref_t3' => 't3'],
            ],
            't3' => [
                'pk' => ['pk3'],
                'columns' => ['pk3', 'parent_id', 'col3'],
                'foreign_keys' => ['parent_id' => 't3'],
            ],
        ]);

        $entrypointConf = [
            'start' => 't1',
            'route-settings' => [
                [
                    'route' => ['t1', 't2'],
                    'filter' => 'col2 > 0',
                ]
            ],
            't3' => [
                'tree' => 2
            ],
        ];

        $transformer = $this->createMock(TransformerInterface::class);
        $settingsFactory = new SettingsFactory($schema, ['trf' => $transformer]);

        $nodeFactory = new NodeFactory($schema, $settingsFactory);

        $n1 = $nodeFactory->create($entrypointConf);

        $this->assertEquals('t1', $n1->name);
        $this->assertEquals(['pk1', 'ref_t2', 'col1'], $n1->settings->columns);
        $this->assertEquals(1, $n1->settings->count);
        $this->assertEquals(null, $n1->settings->whereFilter);

        $this->assertCount(1, $n1->children);
        $n2 = Arr::first($n1->children);

        $this->assertEquals('t2', $n2->name);
        $this->assertEquals(['pk2', 'ref_t3', 'col2'], $n2->settings->columns);
        $this->assertEquals(1, $n2->settings->count);
        $this->assertEquals('col2 > 0', $n2->settings->whereFilter->where);

        $this->assertCount(1, $n2->children);
        $n3 = Arr::first($n2->children);

        $this->assertEquals('t3', $n3->name);
        $this->assertEquals(['pk3', 'parent_id', 'col3'], $n3->settings->columns);
        $this->assertEquals(1, $n3->settings->count);
        $this->assertEquals(null, $n3->settings->whereFilter);

        $this->assertCount(2, $n3->children);

        $path = Traverse::travelPath($n3);
        $actual = [];
        foreach ($path as $p) {
            $ps = [];
            foreach ($p as $n) {
                $ps[] = $n->name;
            }
            $actual[] = $ps;
        }

        $this->assertEquals(array_fill(0, 4, ['t3', 't3', 't3']), $actual);
    }
}
