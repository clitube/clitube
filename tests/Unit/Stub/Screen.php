<?php

declare(strict_types=1);

namespace CliTube\Tests\Unit\Stub;

use CliTube\Internal\Screen\AbstractScreen;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @internal Should be used in tests only
 */
class Screen extends AbstractScreen
{
    public function __construct()
    {
        parent::__construct(new NullOutput());
    }

    public function substr(string $string, int $start, int $length = null, bool $markup = false): string
    {
        return parent::substr($string, $start, $length, $markup);
    }

    public function strlen(string $string): int
    {
        return parent::strlen($string);
    }
}
