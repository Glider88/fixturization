<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

interface SchemaGeneratorInterface
{
    public function generate(): array;
}
