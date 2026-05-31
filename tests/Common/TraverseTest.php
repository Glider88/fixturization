<?php declare(strict_types=1);

namespace Tests\Glider88\Fixturization\Common;

use Glider88\Fixturization\Common\Traverse;
use Glider88\Fixturization\Config\TableSettings;
use Glider88\Fixturization\Schema\TableSchema;
use Glider88\Fixturization\Spider\Node;
use PHPUnit\Framework\TestCase;

class TraverseTest extends TestCase
{
    public function testTravelPath(): void
    {
        /*
        1
          2
            4
            5
              8
              9
          3
            6
            7
        */
        $node = $this->createNodes();

        $actual = [];
        foreach (Traverse::travelPath($node) as $path) {
            $actual[] = array_map(static fn(Node $n) => $n->name, $path);
        }

        $expected = [
            ['N1', 'N2', 'N4'],
            ['N1', 'N2', 'N5', 'N8'],
            ['N1', 'N2', 'N5', 'N9'],
            ['N1', 'N3', 'N6'],
            ['N1', 'N3', 'N7'],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testTravelDeep(): void
    {
        /*
        1
          2
            4
            5
              8
              9
          3
            6
            7
        */
        $node = $this->createNodes();
        $actualNodes = [];
        foreach (Traverse::travelDeep($node) as $n) {
            $actualNodes[] = $n;
        }

        $actual = array_map(static fn(Node $n) => $n->name, $actualNodes);
        $this->assertEquals(['N1', 'N2', 'N4', 'N5', 'N8', 'N9', 'N3', 'N6', 'N7'], $actual);
    }

    private function createNodes(): Node
    {
        $nodeFn = static fn(string $name, array $children) => new Node(
            name: $name,
            alias: 'alias',
            link: null,
            schema: new TableSchema(
                name: 'table schema',
                pks: [],
                cols: []
            ),
            settings: new TableSettings(
                name: 'name',
                count: 1,
                columns: [],
                whereFilter: null,
                transformers: [],
            ),
            children: $children,
        );

        /*
        1
          2
            4
            5
              8
              9
          3
            6
            7
        */
        $n8 = $nodeFn('N8', []);
        $n9 = $nodeFn('N9', []);
        $n5 = $nodeFn('N5', [$n8, $n9]);
        $n4 = $nodeFn('N4', []);
        $n2 = $nodeFn('N2', [$n4, $n5]);
        $n6 = $nodeFn('N6', []);
        $n7 = $nodeFn('N7', []);
        $n3 = $nodeFn('N3', [$n6, $n7]);
        $n1 = $nodeFn('N1', [$n2, $n3]);

        return $n1;
    }
}
