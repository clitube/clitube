<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen\Style;

enum Effect: int implements StyleStuff
{
    /** @see Style::STYLE_RESET_VALUE */
    case Reset = 0;
    case Bold = 1;
    case Faint = 2;
    case Green = 3;
    case Italic = 4;
    case Underline = 5;
    case Blink = 6;

    use StyleTrait;
}
