<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

readonly class Link
{
    public function __construct(
        public LinkType $type,
        public string   $parentTable,
        public string   $parentColumn,
        public string   $table,
        public string   $column,
    ) {}
}
