<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen\Style;

enum Color
{
    case Black;
    case Red;
    case Green;
    case Yellow;
    case Blue;
    case Magenta;
    case Cyan;
    case White;
    case Default;

    public function foreground(): Foreground
    {
        return Foreground::from($this->getValue() + 30);
    }

    public function background(): Background
    {
        return Background::from($this->getValue() + 40);
    }

    private function getValue(): int
    {
        return match ($this) {
            self::Black => 0,
            self::Red => 1,
            self::Green => 2,
            self::Yellow => 3,
            self::Blue => 4,
            self::Magenta => 5,
            self::Cyan => 6,
            self::White => 7,
            self::Default => 9,
        };
    }
}
