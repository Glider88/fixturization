<?php declare(strict_types=1);

namespace Glider88\Fixturization\Spider;

readonly class Node {
    /** @param array<Node> $children */
    public function __construct(
        public string $tableName,
        public array $children = [],
    ) {}
}
