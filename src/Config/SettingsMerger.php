<?php declare(strict_types=1);

namespace Glider88\Fixturization\Config;

use Glider88\Fixturization\Common\Arr;

readonly class SettingsMerger
{
    public function __construct(
        private array $allSettings,
    ) {}

    public function merge(array $entrypointSettings): array
    {
        $merged = array_merge_recursive($this->allSettings, $entrypointSettings);
        $countFixed = $this->fixCount($merged);
        $fixed = $this->fixDoubled($countFixed);

        return $fixed;
    }

    private function fixCount(array $settings): array
    {
        function define(string $count): int
        {
            $vals = explode('-', $count);
            if (count($vals) === 1) {
                return (int) Arr::first($vals);
            }

            return random_int((int) Arr::first($vals), (int) Arr::last($vals));
        }

        $newSettings = Arr::walk($settings, static function ($key, $value) {
            if ($key === 'count') {
                if (is_string($value)) {
                    return define($value);
                }

                if (is_array($value)) {
                    $last = Arr::last($value);
                    if (is_int($last)) {
                        return $last;
                    }

                    if (is_string($last)) {
                        return define($last);
                    }
                }
            }

            return $value;
        });

        return $newSettings;
    }

    private function fixDoubled(array $settings): array
    {
        $newSettings = Arr::walk($settings, static function ($key, $value) {
            if ($key === 'filters' || $key === 'transformers') {
                return array_unique($value);
            }

            return $value;
        });

        return $newSettings;
    }
}
