<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

readonly class TableMeta
{
    /**
     * @param array<int|string> $pk
     * @param array<string> $cols
     * @param array<string, string> $refs ref table name -> ref table foreign key
     */
    public function __construct(
        public string $name,
        public array  $pk,
        public array  $cols,
        public array  $refs,
    ) {}
}
