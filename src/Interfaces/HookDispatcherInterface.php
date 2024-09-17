<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

interface HookDispatcherInterface
{
    /**
     * Check if the hook exists
     *
     * @param string $name the hook name
     * @param ?callable $callback the callback
     * @param ?int $priority the priority
     * @return bool true if the hook exists otherwise false
     */
    public function has(string $name, ?callable $callback = null, ?int $priority = 10): bool;

    /**
     * Add the hook
     *
     * @param string $name the hook name
     * @param callable $callback the callback
     * @param int $priority the higher the priority the earlier the hook will be called
     * @return bool true if the hook is added otherwise false
     */
    public function add(string $name, callable $callback, int $priority = 10): bool;

    /**
     * Remove the hook
     *
     * @param string $name the hook name
     * @param ?callable $callback null to remove all
     * @param ?int $priority the priority
     * @return bool
     */
    public function remove(string $name, ?callable $callback = null, ?int $priority = 10): bool;

    /**
     * Run the hook
     *
     * @param string $name the hook name
     * @param mixed $arg the first argument
     * @param mixed ...$args the rest of arguments
     * @return mixed
     */
    public function run(string $name, $arg, ...$args);
}
