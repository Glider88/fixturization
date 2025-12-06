<?php declare(strict_types=1);

namespace Glider88\Fixturization\Common;

readonly class Sql
{
    public static function fixOpVal($val): array
    {
        $op = match (true) {
            is_null($val)  => 'is',
            is_array($val) => 'in',
            default        => '=',
        };

        $val = self::fixVal($val);

        return [$op, $val];
    }

    public static function fixVal($val)
    {
        return match (true) {
            is_string($val) => "'$val'",
            is_bool($val)   => $val ? 'true' : 'false',
            is_null($val)   => 'null',
            is_array($val)  => '(' . implode(',', array_map(self::fixVal(...), $val)) . ')',
            default         => $val,
        };
    }
}
