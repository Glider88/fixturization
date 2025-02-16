<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

readonly class WhereClause
{
    public function __construct(
        public string $where,
    ) {}

    public function __toString(): string
    {
        return $this->where;
    }

    public static function new(string $column, string $operator, mixed $value): WhereClause
    {
        $fixValueFn = static function(string $op, mixed $val): array
        {
            if (is_string($val)) {
                $val = "'$val'";
            }

            if (is_bool($val)) {
                $val = $val ? 'true' : 'false';
            }

            if (is_null($val)) {
                $op = 'is';
                $val = 'null';
            }

            return [$op, $val];
        };

        [$operator, $value] = $fixValueFn($operator, $value);
        if (is_array($value)) {
            $operator = 'in';
            $value = '(' . implode(',', array_map($fixValueFn(...), $value)) . ')';
        }

        return new WhereClause("$column $operator $value");
    }
}
