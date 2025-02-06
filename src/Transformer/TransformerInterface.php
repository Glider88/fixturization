<?php declare(strict_types=1);

namespace Glider88\Fixturization\Transformer;

interface TransformerInterface
{
    /**
     * @template T
     * @param T $value
     * @return T
     */
    public function transform($value);
}


