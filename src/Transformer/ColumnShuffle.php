<?php declare(strict_types=1);

namespace Glider88\Fixturization\Transformer;

class ColumnAnonymizer implements TransformerInterface
{
    public function transform($value)
    {
        if (is_string($value)) {
            return str_shuffle($value);
        }

        return $value;
    }
}
