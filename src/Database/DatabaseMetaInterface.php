<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

interface DatabaseMetaInterface
{
    /** @return array<string> */
    public function tables(): array;

    /** @return array<string> */
    public function columns(string $table): array;

    /** @return array<string> */
    public function foreignKeys(string $table): array;

    /** @return array<string> */
    public function primaryKeys(string $table): array;
}
