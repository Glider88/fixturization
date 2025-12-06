<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

readonly class TableSchema
{
    /**
     * @param list<string> $pks
     * @param list<string> $cols
     */
    public function __construct(
        public string $name,
        public array  $pks,
        public array  $cols,
    ) {}
}
