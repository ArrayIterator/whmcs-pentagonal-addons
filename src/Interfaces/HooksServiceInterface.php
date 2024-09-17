<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

interface HooksServiceInterface extends RepeatableServiceInterface
{
    /**
     * @param HookInterface|class-string<HookInterface> $hook
     * @return bool true if succeed queued otherwise false
     */
    public function queue($hook): bool;

    /**
     * Check if hook is queued
     *
     * @param HookInterface|class-string<HookInterface> $hook
     * @return bool
     */
    public function queued($hook): bool;

    /**
     * Check if hook dispatched
     *
     * @param HookInterface|class-string<HookInterface> $hook
     * @return bool
     */
    public function dispatched($hook): bool;

    /**
     * @return array<HookInterface>
     */
    public function getQueued(): array;

    /**
     * @param string $hookName
     * @return bool
     */
    public function containHook(string $hookName): bool;

    /**
     * Dispatch the hooks
     */
    public function dispatch(...$args);
}
