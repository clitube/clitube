<?php

declare(strict_types=1);

namespace CliTube\Internal\Screen;

use CliTube\Contract\Pagination\OffsetPaginator;
use CliTube\Contract\Pagination\Paginator as PaginatorInterface;
use CliTube\Internal\Screen\Line\Line;
use ErrorException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @internal
 */
final class Paginator extends AbstractScreen
{
    /** @var null|array<int, string> */
    private ?array $frameCache = null;
    private PaginatorInterface $paginator;
    /** @var array<int, string> */
    private array $tableLines = [];
    private int $lineOffset = 0;
    private ?\Closure $pageStatusCallable = null;

    /**
     * Get possible limit value for the paginator based on screen size and rendering template.
     *
     * @return positive-int Limit value for paginator
     */
    public function getBodySize(): int
    {
        /** 6 =
         * + 1 Header line
         * + 3 Table borders
         * + 1 {@see self::$pageInput}
         * + 1 {@see self::$pageStatus}
         */
        return \max(1, $this->getWindowHeight() - 6);
    }

    public function showNext(): void
    {
        // Set new Offset
        $screenLength = $this->getWindowWidth();
        $longestLine = \max($screenLength, ...\array_map($this->strlen(...), $this->tableLines));
        $maxOffset = $longestLine - $screenLength;
        \assert($maxOffset >= 0);

        if ($maxOffset <= $this->lineOffset) {
            $this->lineOffset = 0;
        } else {
            $this->lineOffset = \min($maxOffset, $this->lineOffset + (int)\ceil($screenLength * .3));
        }
        // Redraw cached frame
        $this->frameCache = null;
        $this->prepareFrame();
    }

    public function setPaginator(PaginatorInterface $paginator): void
    {
        $this->paginator = $paginator;
        $this->tableLines = \array_filter(\explode("\n", $this->renderTable()));
        $this->frameCache = null;
        $this->prepareFrame();
    }

    /**
     * @param null|callable(self $sreen):string $callable
     */
    public function pageStatusCallable(?callable $callable): void
    {
        try {
            $this->pageStatusCallable = $callable !== null
                ? @($callable(...)->bindTo($this) ?: $callable(...)->bindTo(null))
                : null;
        } catch (ErrorException) {
            $this->pageStatusCallable = $callable !== null
                ? $callable(...)->bindTo(null)
                : null;
        }
    }

    protected function prepareFrame(): array
    {
        if ($this->frameCache !== null) {
            return $this->frameCache;
        }
        $maxLength = $this->getWindowWidth();
        $maxHeight = \max(1, $this->getWindowHeight() - 2);
        $result = [];
        // [$line, $column] = $this->cursor;
        foreach ($this->tableLines as $line) {
            $result[] = $this->substr($line, $this->lineOffset, $maxLength, true);
        }

        // Render Status and Input
        $this->pageStatus = new Line(
            (string)($this->pageStatusCallable === null ? null : ($this->pageStatusCallable)($this)),
            length: $maxLength,
        );
        $this->pageInput = $this->substr($this->renderPaginatorBar() . '  ', 0, $maxLength, true);

        // Render blank lines after the table
        $result = \array_merge($result, \array_fill(0, $maxHeight - \count($result), ''));

        return $result;
    }

    protected function renderTable(): string
    {
        $output = new BufferedOutput(
            decorated: $this->output->isDecorated(),
            formatter: $this->output->getFormatter(),
        );
        $data = [];
        $headers = null;
        foreach ($this->paginator as $line) {
            $headers ??= \array_keys($line);
            $data[] = $line;
        }
        if ($data === []) {
            (new Table($output))
                ->addRow(['Empty table'])
                ->render();
        } else {
            (new Table($output))
                ->setHeaders($headers)
                ->setRows($data)
                ->render();
        }

        return $output->fetch();
    }

    protected function renderPaginatorBar(): string
    {
        if (!$this->paginator instanceof OffsetPaginator) {
            // Print unlimited pagination bar
            return \sprintf(
                '%s %s %s',
                $this->wrap('<', 93),
                $this->wrap('-', 32),
                $this->wrap('>', 93),
            );
        }

        $countAll = $this->paginator->getCount();
        $offset = $this->paginator->getOffset();
        $limit = $this->paginator->getLimit();
        $page = (int)\ceil($offset / $limit) + 1;
        $maxPage = \max(1, (int)\ceil($countAll / $limit));

        $dots = $this->wrap('..', 36);
        $pages = \array_filter([
            ...($page <= 4 ? \range(1, $page) : [1, $dots, $page - 1]),
            $this->wrap($page, 42),
            ...(($page > $maxPage - 4) ? \range($page, $maxPage) : [$page + 1, $dots, $maxPage]),
        ], static fn (string|int $num): bool => $num !== $page);

        return \sprintf(
            "%s %s %s  Total %s",
            $this->wrap('<< <', $page > 1 ? 32 : 33),
            \implode(' ', \array_map(fn (string|int $num) => \is_string($num) ? $num :  $this->wrap($num, 32), $pages)),
            $this->wrap('> >>', $page * $limit <= $countAll ? 32 : 33),
            $this->wrap($countAll, 36),
        );
    }

    private function wrap(string|int|null $str, int|string $code): string
    {
        return $str === '' || $str === null ? '' : "\033[{$code}m$str\033[0m";
    }
}
