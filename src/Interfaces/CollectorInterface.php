<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

use ArrayAccess;
use Countable;
use Iterator;
use SeekableIterator;

/**
 * @template TKey
 * @template-covariant TValue
 * @template-implements \Traversable<TKey, TValue>
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
interface CollectorInterface extends Iterator, SeekableIterator, ArrayAccess, Countable
{
    /**
     * Unshift data
     *
     * @param mixed $data
     * @return int
     */
    public function unshift($data): int;

    /**
     * @param mixed $data
     */
    public function push($data): int;

    /**
     * Pop data
     *
     * @return mixed|false
     */
    public function pop();

    /**
     * @return mixed|false
     */
    public function shift();
}
