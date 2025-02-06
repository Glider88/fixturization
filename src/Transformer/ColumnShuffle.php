<?php declare(strict_types=1);

namespace Glider88\Fixturization\Transformer;

class ColumnShuffle implements TransformerInterface
{
    public function transform($value)
    {
        if (is_string($value)) {
            return str_shuffle($value);
        }

        if (is_int($value)) {
            return (int) str_shuffle((string) $value);
        }

        return $value;
    }
}
