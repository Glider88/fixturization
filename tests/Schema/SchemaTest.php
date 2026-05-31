<?php declare(strict_types=1);

namespace Tests\Glider88\Fixturization\Schema;

use Glider88\Fixturization\Schema\Link;
use Glider88\Fixturization\Schema\LinkType;
use Glider88\Fixturization\Schema\Schema;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    public function testLinks(): void
    {
        $schema = new Schema([
            't1' => [
                'pk' => ['c1'],
                'columns' => ['c1', 'c2', 'c3'],
                'foreign_keys' => ['c2' => 't2'],
            ],
            't2' => [
                'pk' => ['c1'],
                'columns' => ['c1', 'c2', 'c3'],
                'foreign_keys' => [],
            ],
        ]);

        $this->assertEquals(
            [new Link(
                type: LinkType::ManyToOne,
                parentTable: 't1',
                parentColumn: 'c2',
                table: 't2',
                column: 'c1',
            )],
            $schema->links('t1', 't2'),
        );

        $this->assertEquals(
            [new Link(
                type: LinkType::OneToMany,
                parentTable: 't2',
                parentColumn: 'c1',
                table: 't1',
                column: 'c2',
            )],
            $schema->links('t2', 't1')
        );

        $this->assertEquals(['t1', 't2'], $schema->allTables());
    }
}
