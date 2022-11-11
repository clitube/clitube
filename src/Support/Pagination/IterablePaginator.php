<?php

declare(strict_types=1);

namespace CliTube\Support\Pagination;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use SeekableIterator;

/**
 * @internal
 */
class IterablePaginator extends BaseOffsetPaginator
{
    protected SeekableIterator $data;

    public function __construct(iterable $data)
    {
        $this->data = match (true) {
            $data instanceof SeekableIterator => $data,
            \is_array($data) => new ArrayIterator($data),
            default => throw new InvalidArgumentException('Unsupported iterable value.'),
        };
        if ($this->data instanceof Countable) {
            $this->count = \count($data);
        }
    }

    public function __clone()
    {
        $this->data = clone $this->data;
    }

    protected function getContent(): array
    {
        if ($this->buffer === null) {
            // Jump to offset
            $this->data->seek($this->offset);
            $this->buffer = [];
            for ($i = 0; $i < $this->limit && $this->data->valid(); ++$i) {
                $this->buffer[] = $this->data->current();
                $this->data->next();
            }
        }
        return $this->buffer;
    }
}
