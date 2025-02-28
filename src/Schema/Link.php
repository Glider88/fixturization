<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

readonly class Link
{
    public function __construct(
        public LinkType $type,
        public string $ownTable,
        public string $ownColumn, // ToDo: can be array
        public string $linkTable,
        public string $linkColumn, // ToDo: can be array
    ) {}
}
