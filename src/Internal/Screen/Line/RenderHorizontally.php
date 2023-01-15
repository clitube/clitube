<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen\Line;

interface RenderHorizontally
{
    /**
     * @param int<0, max> $length
     */
    public function render(int $length): string;
}
