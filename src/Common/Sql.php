<?php declare(strict_types=1);

namespace Glider88\Fixturization\Common;

readonly class Sql
{
    public static function fixOpVal($val): array
    {
        $val = self::fixVal($val);

        $op = '=';
        if (is_null($val)) {
            $op = 'is';
        }

        if (is_array($val)) {
            $op = 'in';
        }

        return [$op, $val];
    }

    public static function fixVal($val)
    {
        if (is_string($val)) {
            return "'$val'";
        }

        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        }

        if (is_null($val)) {
            return 'null';
        }

        if (is_array($val)) {
            return '(' . implode(',', array_map(self::fixVal(...), $val)) . ')';
        }

        return $val;
    }
}
