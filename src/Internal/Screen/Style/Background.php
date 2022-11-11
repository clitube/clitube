<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen\Style;

enum Background: int implements StyleStuff
{
    case Black = 40;
    case Red = 41;
    case Green = 42;
    case Yellow = 43;
    case Blue = 44;
    case Magenta = 45;
    case Cyan = 46;
    case White = 47;
    case Default = 49;

    use StyleTrait;
}
