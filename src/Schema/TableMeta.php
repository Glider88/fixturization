<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

readonly class TableMeta
{
    /**
     * @param array<string> $pk
     * @param array<string> $cols
     */
    public function __construct(
        public string $name,
        public array  $pk,
        public array  $cols,
    ) {}
}
