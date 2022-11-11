<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen\Style;

class MarkupString
{
    /**
     * Calc visible symbols ignoring markup.
     *
     * @return int<0, max> Count of visible console symbols
     */
    public static function strlen(string $string): int
    {
        $str = \preg_replace('/\\033\\[\\d{1,3}(?:;\\d{1,3})*m/u', '', $string);

        return \mb_strlen($str);
    }

    /**
     * Cut visible symbols with markup.
     *
     * @param bool $markup Should markup be in result or not.
     * @param StyleState $styleState Style state in the beginning of the string.
     */
    public static function substr(
        string $string,
        int $start,
        int $length = null,
        bool $markup = false,
        StyleState $styleState = new StyleState(),
    ): string {
        /** @var array{array{non-empty-string, int<0, max>}, array{non-empty-string, int<0, max>}} $matches */
        \preg_match_all(
            '/\\033\\[(\\d{1,2}(?:;\\d{1,3})*)m/u',
            $string,
            $matches,
            PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL,
        );

        [$markers, $codes] = $matches;
        /**
         * Flip array to Caret position => Marker index
         * @var array<int<0, max>, int> $markersPos
         */
        $markersPos = [];
        foreach ($markers as $key => [$marker, $position]) {
            $markersPos[$position] = $key;
        }

        /** Count of readable symbols */
        $strReadables = static::strlen($string);
        // Calc limitations
        $start = \max(0, $start < 0 ? $strReadables + $start : $start);
        $end = match (true) {
            $length === null => $strReadables,
            $length < 0 => \max($start, $strReadables + $length),
            default => \min($strReadables, $start + $length),
        };
        if ($start >= $strReadables || $start === $end) {
            return '';
        }

        $offset = 0;
        $offsetSym = 0;
        // Markup-less symbols caret
        $caret = 0;
        $result = '';
        $inRange = false;

        do {
            if (\array_key_exists($offset, $markersPos)) {
                $position = $markersPos[$offset];
                $marker = $markers[$position];
                $offset += \strlen($marker[0]);
                $offsetSym += \mb_strlen($marker[0]);

                if ($markup) {
                    if ($inRange) {
                        $result .= $marker[0];
                    }
                    // Parse markup codes and store state
                    $styleState = $styleState->withStyle(...Style::tryFromMultiple($codes[$position][0]));
                }
                continue;
            }

            // Get symbol
            $symbol = $markup ? \mb_substr($string, $offsetSym, 1) : \substr($string, $offset, 1);
            ++$offsetSym;
            $offset += \strlen($symbol);
            // In range
            $previousInRange = $inRange;
            $inRange = $caret >= $start;
            if ($previousInRange !== $inRange && $markup) {
                $result .= $styleState->getMarkup();
            }
            if ($caret >= $start) {
                $result .= $symbol;
            }
            ++$caret;
        } while ($caret < $end);

        if ($styleState->count() > 0) {
            $result .= Effect::Reset->string();
        }

        return $result;
    }
}
