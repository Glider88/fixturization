<?php declare(strict_types=1);

namespace Glider88\Fixturization\Schema;

interface SchemaMergerInterface
{
    public function merge(array ...$schemas): array;
}
