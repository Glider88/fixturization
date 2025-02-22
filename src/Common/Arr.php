<?php declare(strict_types=1);

namespace Glider88\Fixturization\Common;

readonly class Arr
{
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

    /**
     * @template T
     * @param non-empty-array<T> $array
     * @return T
     */
    public static function head(array $array)
    {
        return self::first($array);
    }

    /**
     * @template T
     * @param array<T> $array
     * @return array<T>
     */
    public static function tail(array $array): array
    {
        return array_slice($array, 1);
    }

    /**
     * @param array $a
     * @param callable(int|string, mixed): mixed $cb
     * @return array
     */
    public static function walk(array $a, callable $cb): array
    {
        $b = [];
        foreach ($a as $k => $v) {
            $v = $cb($k, $v);
            if (is_array($v)) {
                $b[$k] = self::walk($v, $cb);
            } else {
                $b[$k] = $v;
            }
        }

        return $b;
    }

    /**
     * @template K
     * @template T
     * @param array<K,T> $array
     * @return array<array<K,T>>
     */
    public static function slidingWindow(array $array, int $size, bool $preserveKeys = false, int $step = 1): array
    {
        $result = [];
        $length = count($array);
        foreach (range(0, $length, $step) as $offset) {
            $window = array_slice($array, $offset, $size, $preserveKeys);
            $result[] = $window;
            if ($offset + $size >= $length) {
                break;
            }
        }

        return $result;
    }
}
