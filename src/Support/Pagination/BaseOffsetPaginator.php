<?php

declare(strict_types=1);

namespace CliTube\Support\Pagination;

use CliTube\Contract\Pagination\OffsetPaginator;
use Traversable;

abstract class BaseOffsetPaginator implements OffsetPaginator
{
    protected ?array $buffer = null;
    protected int $limit = 1;
    protected ?int $count = null;
    protected int $offset = 0;
    protected int $pages = 1;
    protected int $page = 1;

    public function getIterator(): Traversable
    {
        yield from $this->getContent();
    }

    public function withLimit(int $limit): static
    {
        $clone = clone $this;
        $clone->limit = $limit;
        $clone->calc();
        return $clone;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function nextPage(): static
    {
        $clone = clone $this;
        $clone->offset = \min(($clone->pages - 1) * $clone->limit, $clone->limit * $clone->page);
        $clone->calc();
        return $clone;
    }

    public function previousPage(): static
    {
        $clone = clone $this;
        $clone->offset = \max(0, $clone->limit * ($clone->page - 2));
        $clone->calc();
        return $clone;
    }

    public function count(): int
    {
        return \count($this->getContent());
    }

    public function withOffset(int $offset): static
    {
        $clone = clone $this;
        $clone->offset = $offset;
        $clone->calc();
        return $clone;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    protected function calc(): void
    {
        $this->buffer = null;
        if ($this->count !== null) {
            $this->pages = \max(1, (int)\ceil($this->count / $this->limit));
        }
        $this->page = (int)\ceil($this->offset / $this->limit) + 1;
    }

    abstract protected function getContent(): array;
}
