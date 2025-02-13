<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Spider\Node;

readonly class Entrypoint
{
    /** @param array<Node> $roots */
    public function __construct(
        public array $roots,
        public Settings $settings,
    ) {}
}
