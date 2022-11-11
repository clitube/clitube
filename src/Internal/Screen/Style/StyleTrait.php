<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen\Style;

trait StyleTrait
{
    /**
     * @return non-empty-string
     */
    public function string(StyleStuff ...$with): string
    {
        $values = [$this->value, ...\array_map(static fn (StyleStuff $enum) => $enum->value, $with)];
        return \sprintf('[%sm', \implode(';', $values));
    }

    /**
     * @psalm-assert-if-true false $this->isEffect()
     * @psalm-assert-if-false true $this->isEffect()
     */
    public function isColor(): bool
    {
        return !$this->isEffect();
    }

    /**
     * @psalm-assert-if-true false $this->isColor()
     * @psalm-assert-if-false true $this->isColor()
     */
    public function isEffect(): bool
    {
        return $this instanceof Effect;
    }
}
