<?php declare(strict_types=1);

namespace Glider88\Fixturization\FileGenerator;

interface SqlTransformerInterface
{
    public function sql(array $data): string;
}
