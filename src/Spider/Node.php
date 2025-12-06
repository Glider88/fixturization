<?php declare(strict_types=1);

namespace Glider88\Fixturization\Spider;

use Glider88\Fixturization\Config\TableSettings;
use Glider88\Fixturization\Schema\Link;
use Glider88\Fixturization\Schema\TableSchema;

readonly class Node {
    /** @param list<Node> $children */
    public function __construct(
        public string        $name,
        public string        $alias,
        public ?Link         $link,
        public TableSchema   $schema,
        public TableSettings $settings,
        public array         $children = [],
    ) {}
}
