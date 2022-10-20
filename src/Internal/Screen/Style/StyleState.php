<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen\Style;

use Countable;

/**
 * @psalm-immutable
 */
final class StyleState implements Countable
{
    /** @var array<class-string<StyleStuff>, StyleStuff> */
    private array $style = [];

    public function withStyle(StyleStuff ...$stuffs): self
    {
        $clone = clone $this;

        foreach ($stuffs as $stuff) {
            $clone->style[$stuff::class] = $stuff;
            if ($stuff->value === Style::STYLE_RESET_VALUE) {
                $clone->style = [];
            }
        }
        return $clone;
    }

    public function getMarkup(): string
    {
        return $this->style === []
            ? ''
            : \sprintf(
                "\033[%sm",
                \implode(';', \array_map(static fn (StyleStuff $stuff) => $stuff->value, $this->style)),
            );
    }

    /**
     * @template T of StyleStuff
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function getStuffValue(string $class): ?StyleStuff
    {
        $result = $this->style[$class] ?? null;
        \assert($result === null || $result instanceof $class);
        return $result;
    }

    public function count(): int
    {
        return \count($this->style);
    }
}
