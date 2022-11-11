<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen\Style;

class Style
{
    public const STYLE_RESET_VALUE = 0;

    public static function tryFrom(int $code): ?StyleStuff
    {
        return Effect::tryFrom($code) ?? Foreground::tryFrom($code) ?? Background::tryFrom($code);
    }

    /**
     * @param int|string ...$codes Can be string of int separated with `;`
     *
     * @return array<int, StyleStuff>
     */
    public static function tryFromMultiple(int|string ...$codes): array
    {
        $result = [];
        foreach (
            \array_merge(
                ...\array_map(static fn(int|string $set): array => \explode(';', (string)$set), $codes),
            ) as $value
        ) {
            $stuff = static::tryFrom((int)$value);
            if ($stuff !== null) {
                $result[] = $stuff;
            }
        }
        return $result;
    }
}
