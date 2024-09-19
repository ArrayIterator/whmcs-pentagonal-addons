<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries;

use Pentagonal\Neon\WHMCS\Addon\Interfaces\CollectorInterface;
use function array_key_exists;
use function array_pop;
use function array_push;
use function array_shift;
use function array_unshift;
use function count;
use function current;
use function key;
use function next;
use function reset;

/**
 * Class Collector for collecting data
 *
 * @template TKey
 * @template-covariant TValue
 * @template-implements \Traversable<TKey, TValue>
 * @template TIterable of iterable<TKey,TValue>|array<TKey,TValue>
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class Collector implements CollectorInterface
{
    /**
     * @var TIterable $data the iterable data
     */
    protected array $data = [];

    /**
     * @param TIterable $data
     */
    public function __construct(iterable $data = [])
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function unshift($data): int
    {
        return array_unshift($this->data, $data);
    }

    /**
     * @inheritDoc
     */
    public function push($data): int
    {
        return array_push($this->data, $data);
    }

    /**
     * @inheritDoc
     */
    public function pop()
    {
        return array_pop($this->data);
    }

    /**
     * @inheritDoc
     */
    public function shift()
    {
        return array_shift($this->data);
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        return next($this->data);
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return key($this->data) !== null;
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        return reset($this->data);
    }

    /**
     * @inheritDoc
     */
    public function seek($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->data);
    }
}
