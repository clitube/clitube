<?php

declare(strict_types=1);

namespace CliTube\Tests\Unit\Screen;

use CliTube\Internal\Screen\Style\Background;
use CliTube\Internal\Screen\Style\Effect;
use CliTube\Internal\Screen\Style\Foreground;
use PHPUnit\Framework\TestCase;
use CliTube\Tests\Unit\Stub\Screen;

class AbstractScreenTest extends TestCase
{
    public function stringLengthProvider(): iterable
    {
        return [
            ['foo', 3],
            ['', 0],
            [Foreground::Yellow->string() . 'bar' . Foreground::White->string(), 3],
            [Background::Green->string(Foreground::Black, Effect::Bold) . Effect::Reset->string(), 0],
            [Background::Green->string(Foreground::Black) . 'bAr' . Effect::Reset->string(), 3],
        ];
    }

    /**
     * @dataProvider stringLengthProvider
     */
    public function testStrlen(string $string, int $expectedLength): void
    {
        $screen = new Screen();
        $this->assertSame($expectedLength, $screen->strlen($string));
    }

    public function colorStringSubstrProvider(): iterable
    {
        $simpleString = 'Foo bar baz fiz kez dez';
        $colorString = $this->wrap('Yellow', 33);
        $colorString2 = $this->wrap('Green ' . $this->wrap('Yellow', 33) . ' back', 42);
        $colorString3 = Background::Green->string(Foreground::Black, Effect::Bold) . 'foo' . Effect::Reset->string();
        return [
            // Without markup
            [$simpleString, 0, null, $simpleString],
            [$simpleString, 0, 99, $simpleString],
            [$simpleString, 0, -1, \substr($simpleString, 0, -1)],
            // With markup
            [$colorString, 0, 6, $colorString],
            [$colorString, 0, PHP_INT_MAX, $colorString],
            [$colorString, 0, 1, $this->wrap('Y', 33)],
            [$colorString, 2, 2, $this->wrap('ll', 33)],
            [$colorString, 3, null, $this->wrap('low', 33)],
            [$colorString, 3, -1, $this->wrap('lo', 33)],
            [$colorString3, -1, null, Background::Green->string(Foreground::Black, Effect::Bold) . 'o' . Effect::Reset->string()],
            // todo unicode
        ];
    }

    /**
     * @dataProvider colorStringSubstrProvider
     */
    public function testSubstrWithStyles(string $string, int $start, ?int $length, string $expected): void
    {
        $screen = new Screen();
        $this->assertSame($expected, $screen->substr($string, $start, $length, true));
    }

    public function stringSubstrProvider(): iterable
    {
        $simpleString = 'Foo bar baz fiz kez dez';
        $colorString = $this->wrap('Yellow', 33);
        $colorString2 = Background::Green->string(Foreground::Black, Effect::Bold) . 'foo' . Effect::Reset->string();
        return [
            // Without markup
            [$simpleString, 0, null, $simpleString],
            [$simpleString, 0, 99, $simpleString],
            [$simpleString, 0, -1, \substr($simpleString, 0, -1)],
            [$simpleString, 0, -200, \substr($simpleString, 0, -200)],
            [$simpleString, -200, 2, \substr($simpleString, -200, 2)],
            [$simpleString, 5, -200, \substr($simpleString, 5, -200)],
            // With markup
            [$colorString, 0, 6, 'Yellow'],
            [$colorString, 0, PHP_INT_MAX, 'Yellow'],
            [$colorString, 0, 1, 'Y'],
            [$colorString, 2, 2, 'll'],
            [$colorString, 3, null, 'low'],
            [$colorString, 3, -1, 'lo'],
            [$colorString2, -1, null, 'o'],
            // todo unicode
        ];
    }

    /**
     * @dataProvider stringSubstrProvider
     */
    public function testSubstrNoMarkup(string $string, int $start, ?int $length, string $expected): void
    {
        $screen = new Screen();
        $this->assertSame($expected, $screen->substr($string, $start, $length, false));
    }

    private function wrap(string|int $str, int|string $code): string
    {
        return "\033[{$code}m$str\033[0m";
    }
}
