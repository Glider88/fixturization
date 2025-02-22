<?php declare(strict_types=1);

namespace Glider88\Fixturization\Database;

interface WhereClauseInterface
{
    public function __toString(): string;
}
