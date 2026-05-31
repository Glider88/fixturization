<?php declare(strict_types=1);

namespace Tests\Glider88\Fixturization\Common;

use Glider88\Fixturization\Common\Sql;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SqlTest extends TestCase
{
    public static function fixOpValProvider(): array
    {
        return [
            [null, 'is null'],
            [ 0, '= 0'],
            [ 1, '= 1'],
            [-1, '= -1'],
            ['a', "= 'a'"],
            ['',  "= ''"],
            [true,  "= true"],
            [false, "= false"],
            [0.7, '= 0.7'],
            [[1, 'two', null], "in (1,'two',null)"],
        ];
    }

    #[DataProvider('fixOpValProvider')]
    public function testFixOpVal($val, string $expected): void
    {
        $actual = Sql::fixOpVal($val);
        $this->assertEquals($expected, implode(' ', $actual));
    }
}
