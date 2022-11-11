<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen;

use CliTube\Internal\Screen\Style\MarkupString;
use CliTube\Internal\Screen\Style\Style;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/**
 * @internal
 */
class AbstractScreen
{
    /**
     * Overwrite last output
     */
    public bool $overwrite = true;

    protected array $buffer = [''];

    protected Terminal $terminal;

    protected ?string $pageStatus = '';
    protected ?string $pageInput = '';

    protected bool $firstDraw = true;
    protected int $redrawLines = 0;

    public function __construct(
        protected readonly OutputInterface $output,
    ) {
        $this->terminal = new Terminal();

        if (!$this->output->isDecorated()) {
            // disable overwrite when output does not support ANSI codes.
            $this->overwrite = false;
        }
    }

    public function clear(bool $cleanInputLine = true): void
    {
        if ($this->overwrite) {
            if (!$this->firstDraw) {
                $this->removeLines($this->redrawLines, $cleanInputLine);
            }
        } else {
            $this->output->writeln('');
        }
    }

    public function redraw(bool $cleanInputLine = false): void
    {
        $this->clear($cleanInputLine);
        $frame = $this->prepareFrame();
        $this->firstDraw = false;
        $this->redrawLines = \count($frame) + 1;

        $this->drawFrame($frame);
    }

    /**
     * @return string[]
     */
    protected function prepareFrame(): array
    {
        return $this->buffer;
    }

    /**
     * @param string[] $frame
     */
    protected function drawFrame(array $frame): void
    {
        // $i = 0;
        $frame[] = $this->pageInput;
        $this->output->write($frame, true, OutputInterface::OUTPUT_RAW);
        $this->output->write($this->pageStatus, false, OutputInterface::OUTPUT_RAW);
        // Move cursor to Input
        $this->output->write(sprintf("\x1b[%dA", 1));
        $this->output->write(sprintf("\x1b[%dG", $this->strlen($this->pageInput) + 1));
    }

    public function removeLines(int $lines = 1, bool $cleanInputLine = true): void
    {
        if ($lines < 0) {
            return;
        }
        // $this->output->write(\str_repeat("\x1B[1A\x1B[2K", $lines), options: OutputInterface::OUTPUT_RAW);
        $cursor = new \Symfony\Component\Console\Cursor($this->output);
        if ($cleanInputLine) {
            $cursor->clearLine();
        }
        for ($i =0; $i < $lines; ++$i) {
            $cursor->moveUp();
            $cursor->clearLine();
        }
        $cursor->moveToColumn(0);

        // # Move the cursor to the beginning of the line
        // $this->output->write("\x0D", options: OutputInterface::OUTPUT_RAW);
        // # Erase the line
        // $this->output->write("\x1B[2K", options: OutputInterface::OUTPUT_RAW);
        // # Erase previous lines
        // $this->output->write(\str_repeat("\x1B[1A\x1B[2K", $lines), options: OutputInterface::OUTPUT_RAW);
        // // for ($i = 0; $i < $lines; ++$i)
        // //     $this->output->write("\x1B[1A\x1B[2K");
        // }
    }

    /**
     * @return positive-int
     */
    public function getWindowWidth(): int
    {
        return $this->terminal->getWidth();
    }

    /**
     * @return positive-int
     */
    public function getWindowHeight(): int
    {
        return $this->terminal->getHeight();
    }

    /**
     * @param string[]|string $message
     */
    public function writeln(iterable|string $message): void
    {
        $this->write($message, true);
    }

    /**
     * @param string[]|string $message
     */
    public function write(iterable|string $message, bool $newline = false): void
    {
        $messages = \is_string($message) ? [$message] : $message;
        foreach ($messages as $msg) {
            $same = true;
            foreach (\explode("\n", \str_replace("\r", '', $msg)) as $line) {
                if ($same) {
                    $this->buffer[\array_key_last($this->buffer)] .= $line;
                    $same = false;
                } else {
                    $this->buffer[] = $line;
                }
            }
        }
        if ($newline) {
            $this->buffer[] = '';
        }
    }

    /**
     * Calc visible symbols ignoring markup.
     *
     * @return int<0, max> Count of visible console symbols
     */
    protected function strlen(string $string): int
    {
        return MarkupString::strlen($string);
    }

    /**
     * Cut visible symbols with markup.
     * todo
     */
    protected function substr(string $string, int $start, int $length = null, bool $markup = false): string
    {
        return MarkupString::substr($string, $start, $length, $markup);
    }
}
