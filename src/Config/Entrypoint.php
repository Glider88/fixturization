<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

readonly class Entrypoint
{
    public function __construct(
        public string $table,
        public ?int $count,
    ) {}
}
