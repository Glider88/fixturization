<?php declare(strict_types=1);

namespace Glider88\Fixturization\Common;

readonly class Arr
{
    /**
     * @template K
     * @template V
     * @param array<K,V> $array
     * @param positive-int $length
     * @return array<K,V>
     */
    public static function sliceLast(array $array, int $length, bool $preserve_keys = false): array
    {
        return array_slice($array, -$length, $length, $preserve_keys);
    }

    /**
     * @template K
     * @template V
     * @param array<K,V> $array
     * @param positive-int $length
     * @return array<K,V>
     */
    public static function sliceFirst(array $array, int $length, bool $preserve_keys = false): array
    {
        return array_slice($array, 0, $length, $preserve_keys);
    }

    /**
     * @template T
     * @param non-empty-array<T> $array
     * @return T
     */
    public static function first(array $array)
    {
        return $array[array_key_first($array)];
    }

    /**
     * @template T
     * @param non-empty-array<T> $array
     * @return T
     */
    public static function last(array $array)
    {
        return $array[array_key_last($array)];
    }

    public static function rtrim(array $array, array $elements, ?int $length = null): array
    {
        $arrLen = count($array);
        $original = $array;
        $count = 0;
        while (count($array) >= 0) {
            $last = array_pop($array);
            if (! in_array($last, $elements, true)) {
                break;
            }

            if ($length !== null) {
                $length -= 1;
                if ($length < 0) {
                    break;
                }
            }

            $count += 1;
        }

        return array_slice($original, 0, $arrLen - $count, true);
    }
}
