<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen\Line;

use CliTube\Internal\Screen\Style\MarkupString;

/**
 * Just a line of text with a custom style
 */
class Line implements \Stringable, RenderHorizontally
{
    const ALIGN_LEFT = 0;
    const ALIGN_CENTER = 1;
    const ALIGN_RIGHT = 2;

    private readonly string $text;

    /**
     * @param string $text Text to display
     * @param self::ALIGN_* $align one of ALIGN_* constants
     * @param bool $trim If {@see true}, text will be trimmed to fit the width
     * @param int $length Default value that will be used on to string conversion
     */
    public function __construct(
        string $text,
        private readonly int $align = self::ALIGN_LEFT,
        private readonly bool $trim = true,
        private readonly int $length = 80,
    ) {
        $this->text = \str_replace(["\n", "\r"], [' ', ''], $text);
    }

    public function render(int $length): string
    {
        $len = MarkupString::strlen($this->text);
        $left = (int) \abs(
            $this->align === self::ALIGN_LEFT
                ? 0
                : \floor(($length - $len) / ($this->align === self::ALIGN_RIGHT ? 1 : 2))
        );

        if ($len <= $length) {
            $text = \str_repeat(' ', $left) . $this->text;
        } else {
            $text = $this->trim
                ? MarkupString::substr($this->text, $left, $length, markup: true)
                : $this->text;
        }

        return $text;
    }

    public function __toString(): string
    {
        return $this->render($this->length);
    }
}
