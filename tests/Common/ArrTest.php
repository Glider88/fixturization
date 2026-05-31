<?php declare(strict_types=1);

namespace Tests\Glider88\Fixturization\Common;

use Glider88\Fixturization\Common\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{
    #[TestWith([0, []])]
    #[TestWith([3, [3, 4, 5]])]
    #[TestWith([9, [0, 1, 2, 3, 4, 5]])]
    #[TestWith([2, [4 => 4, 5 => 5], true])]
    public function testSliceLast(int $length, array $expected, bool $preserve = false): void
    {
        $actual = Arr::sliceLast(range(0, 5), $length, $preserve);
        $this->assertEquals($expected, $actual);
    }

    #[TestWith([0, []])]
    #[TestWith([3, [0, 1, 2]])]
    #[TestWith([9, [0, 1, 2, 3, 4, 5]])]
    #[TestWith([2, [0, 1]])]
    public function testSliceFirst(int $length, array $expected, bool $preserve = false): void
    {
        $actual = Arr::sliceFirst(range(0, 5), $length, $preserve);
        $this->assertEquals($expected, $actual);
    }

    public static function rtrimProvider(): array
    {
        $arr = [
            'one' => 1,
            'two' => 'b',
            'three' => 3,
            'four' => 'd',
            'five' => 5,
        ];

        return [
            [[],        $arr],
            [['j', 10], $arr],
            [['d', 5],  ['one' => 1, 'two' => 'b', 'three' => 3]],
            [[1, 'b', 3, 'd', 5], []],
            [[1, 'b', 3, 'd', 5], ['one' => 1, 'two' => 'b'], 3],
        ];
    }

    #[DataProvider('rtrimProvider')]
    public function testRtrim(array $elements, array $expected, ?int $length = null): void
    {
        $arr = [
            'one' => 1,
            'two' => 'b',
            'three' => 3,
            'four' => 'd',
            'five' => 5,
        ];

        $actual = Arr::rtrim($arr, $elements, $length);
        $this->assertEquals($expected, $actual);
    }
}
