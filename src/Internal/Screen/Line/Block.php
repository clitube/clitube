<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen\Line;

use CliTube\Internal\Screen\Style\MarkupString;
use Stringable;

/**
 * Flexible text block with prefix
 */
class Block implements Stringable, RenderHorizontally
{
    /**
     * @var iterable<array-key, string|Stringable>
     */
    private iterable $text;

    /**
     * @param string|Stringable|iterable<array-key, string|Stringable> $text
     */
    public function __construct(
        string|Stringable|iterable $text,
        private readonly string $prefix = '',
        private readonly string $postfix = '',
        private readonly int $length = 80,
    ) {
        $this->text = !\is_iterable($text) ? [$text] : $text;
    }

    public function render(int $length): string
    {
        /** @var string[] $text Single lines */
        $text = [];
        $result = [];
        $maxLength = $length - MarkupString::strlen($this->prefix) - MarkupString::strlen($this->postfix);
        if ($maxLength <= 0) {
            return '';
        }

        // Normalize to scalars
        foreach ($this->text as $fragment) {
            $fragment = $fragment instanceof RenderHorizontally
                ? $fragment->render($maxLength)
                : (string)$fragment;

            foreach (\explode("\n", $fragment) as $line) {
                // todo: make a common normalizer instead
                $text[] = \str_replace(["\r", "\t"], ['', '  '], $line);
            }
        }
        if ($text === []) {
            return '';
        }

        $line = $offset = 0;
        // Break lines
        while (isset($text[$line])) {
            $lineLen ??= MarkupString::strlen($text[$line]);
            $result[] = MarkupString::substr($text[$line], $offset, $maxLength, markup: true);
            $offset += $maxLength;
            if ($lineLen - $offset <= 0) {
                $offset = 0;
                ++$line;
            }
        }

        return $this->prefix . \implode("$this->postfix\n$this->prefix", $result) . $this->postfix;
    }

    public function __toString(): string
    {
        return $this->render($this->length);
    }
}
