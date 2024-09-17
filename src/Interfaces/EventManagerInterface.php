<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

use Countable;

/**
 * Interface for event manager
 *
 * @template TFunction of callable
 * @template TParam of mixed
 */
interface EventManagerInterface extends Countable
{
    /**
     * Attach an event
     *
     * @param string $name the event name
     * @param ?TFunction $eventCallback the event callback
     * @param bool $once event is once
     */
    public function attach(string $name, callable $eventCallback, bool $once = false);

    /**
     * Detach the callback
     *
     * @param string $name
     * @param ?TFunction $eventCallback
     * @return int
     */
    public function detach(string $name, ?callable $eventCallback = null): int;

    /**
     * Dispatch the event and return the result
     *
     * @param string $name
     * @param TParam $param
     * @param ...$args
     * @return TParam|mixed
     */
    public function apply(string $name, $param = null, ...$args);

    /**
     * Check if the current event is the same as the given name
     *
     * @param string $name
     * @param callable|null $eventCallback
     * @return bool
     */
    public function is(string $name, ?callable $eventCallback = null): bool;

    /**
     * Check if the callback is in the processing
     *
     * @param string $name
     * @param callable|null $eventCallback
     * @return bool
     */
    public function in(string $name, ?callable $eventCallback = null): bool;

    /**
     * Get current dispatched param
     *
     * @return TParam
     */
    public function getCurrentParam();

    /**
     * Get current event name
     *
     * @return ?string
     */
    public function currentEventName(): ?string;
}
