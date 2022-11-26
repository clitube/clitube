<?php

declare(strict_types=1);

namespace CliTube\Tests\Internal\Screen\Line;

use CliTube\Internal\Screen\Line\Block;
use CliTube\Internal\Screen\Style\Effect;
use CliTube\Internal\Screen\Style\Foreground;
use PHPUnit\Framework\TestCase;

final class BlockTest extends TestCase
{
    public function testSimpleRender(): void
    {
        $block = new Block('foo');
        $this->assertSame('foo', $block->render(3));
        $this->assertSame('foo', $block->render(99));
        $this->assertSame("fo\no", $block->render(2));
        $this->assertSame("f\no\no", $block->render(1));
        $this->assertSame('', $block->render(0));
    }

    public function testRenderWithMarkup(): void
    {
        $block = new Block(Foreground::White->string() . 'foo' . Foreground::Yellow->string() . 'bar');

        $this->assertSame(
            Foreground::White->string() . 'foo' . Foreground::Yellow->string() . 'bar' . Effect::Reset->string(),
            $block->render(6),
        );
        $this->assertSame(
            Foreground::White->string() . 'foo' . Foreground::Yellow->string() . 'bar' . Effect::Reset->string(),
            $block->render(PHP_INT_MAX),
        );
        $this->assertSame(
            Foreground::White->string() . 'foo' . Effect::Reset->string() . "\n"
            . Foreground::Yellow->string() . 'bar' . Effect::Reset->string(),
            $block->render(3),
        );
    }

    public function testRenderWithPrefix(): void
    {
        $block = new Block('Foo bar baz fiz kez dez', prefix: '> ');

        $this->assertSame('> Foo bar baz fiz kez dez', $block->render(99));
        $this->assertSame("> Foo \n> bar \n> baz \n> fiz \n> kez \n> dez", $block->render(6));
    }

    public function testRenderWithBlockInsideWithPrefix(): void
    {
        $block1 = new Block('Foo bar baz fiz kez dez', prefix: '> ');
        $block = new Block($block1, prefix: '| ');

        $this->assertSame('| > Foo bar baz fiz kez dez', $block->render(99));
        $this->assertSame("| > Foo \n| > bar \n| > baz \n| > fiz \n| > kez \n| > dez", $block->render(8));
    }

    public function testRenderWithBlockInsideWithPrefixAndPostfix(): void
    {
        $block1 = new Block('Foo bar baz', prefix: '> ', postfix: ' <');
        $block = new Block($block1, prefix: '| ', postfix: ' |');

        $this->assertSame('| > Foo bar baz < |', $block->render(99));
        $this->assertSame("| > Foo  < |\n| > bar  < |\n| > baz < |", $block->render(12));
    }
}
