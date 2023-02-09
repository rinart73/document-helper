<?php

declare(strict_types=1);

namespace Rinart73\DocumentHelper\Traits;

trait HTMLOperations
{
    /**
     * Renders HTML attribute string `key="value" key2="value2"`.
     * Ignores attributes with `null` value.
     * Renders attributes with empty string `''` value as key only.
     *
     * @param array<string, mixed> $attributes
     */
    protected function renderAttributes(array $attributes): string
    {
        $attributes = array_filter($attributes, static fn ($value) => $value !== null);

        return implode(' ', array_map(
            static function ($value, $name) {
                $name  = trim($name);
                $value = trim((string) $value);
                if ($value === '') {
                    return $name;
                }

                return sprintf('%s="%s"', $name, esc($value));
            },
            $attributes,
            array_keys($attributes)
        ));
    }
}
