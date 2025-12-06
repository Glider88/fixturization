<?php declare(strict_types=1);

namespace Glider88\Fixturization\FileGenerator;

use Glider88\Fixturization\Spider\Result;

interface SqlTransformerInterface
{
    public static function sql(Result $result): string;
}
