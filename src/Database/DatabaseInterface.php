<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

interface DatabaseInterface
{
//    public function randomRows(string $table, array $columns, ?int $count): array;

    public function setSeed(float $seed): void;

    public function unsetSeed(): void;
}
