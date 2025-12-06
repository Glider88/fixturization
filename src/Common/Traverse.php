<?php declare(strict_types=1);

namespace Glider88\Fixturization\Common;

use Generator;
use Glider88\Fixturization\Spider\Node;

readonly class Traverse
{
    /**
     * @param list<Node> $path
     * @return Generator|list<list<Node>>
     */
    public static function travelPath(Node $node, array $path = []): Generator
    {
        $path[] = $node;

        if (empty($node->children)) {
            yield $path;
        }

        foreach ($node->children as $child) {
            yield from self::travelPath($child, $path);
        }
    }

    /** @return Generator | list<Node> */
    public static function travelDeep(Node $node): Generator
    {
        yield $node;

        foreach ($node->children as $child) {
           yield from self::travelDeep($child);
        }
    }
}
