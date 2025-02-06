<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

readonly class WhereClause
{
    private function __construct(
        public string $col,
        public string $operator,
        public mixed $value,
    ) {}

    public static function new(string $column, string $operator, mixed $value): WhereClause
    {
        $fn = static function(string $col, string $op, $val): array
        {
            if (is_string($val)) {
                $val = "'$val'";
            }

            if (is_bool($val)) {
                $val = $val ? 'true' : 'false';
            }

            if (is_null($val)) {
                return [$col, 'is', 'null'];
            }

            if (str_contains(strtolower($op), 'in')) {
                $val = '(' . implode(',', (array) $val) . ')';
            }

            return [$col, $op, $val];
        };

        [$col, $op, $val] = $fn($column, $operator, $value);

        return new WhereClause($col, $op, $val);
    }
}
