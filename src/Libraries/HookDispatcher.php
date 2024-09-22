<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\HookDispatcherInterface;
use Throwable;
use function add_hook;
use function count;
use function is_array;
use function reset;
use function run_hook;

class HookDispatcher implements HookDispatcherInterface
{
    /**
     * @inheritDoc
     */
    public function has(string $name, ?callable $callback = null, ?int $priority = 10): bool
    {
        global $hooks;
        if (!is_array($hooks) || !isset($hooks[$name]) || !is_array($hooks[$name])) {
            return false;
        }
        if ($callback === null) {
            return true;
        }
        foreach ($hooks[$name] as $hook) {
            $hookFunction = $hook['hook_function'] ?? null;
            $hookPriority = $hook['priority'] ?? null;
            if ($hookFunction !== $callback) {
                continue;
            }
            if ($priority === null || $hookPriority === $priority) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function add(string $name, callable $callback, int $priority = 10): bool
    {
        add_hook($name, $priority, $callback);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function remove(string $name, ?callable $callback = null, ?int $priority = 10): bool
    {
        global $hooks;
        if (!is_array($hooks) || !isset($hooks[$name]) || !is_array($hooks[$name])) {
            return false;
        }
        $result = false;
        foreach ($hooks[$name] as $key => $hook) {
            if (($hook['priority'] ?? null) === $priority && ($hook['hook_function'] ?? null) === $callback) {
                unset($hooks[$name][$key]);
                $result = true;
            }
            if (count($hooks[$name]) === 0) {
                unset($hooks[$name]);
            }
        }
        return $result;
    }

    /**
     * @inheritDoc
     *
     * @see run_hook()
     */
    public function run(string $name, $arg, ...$args)
    {
        $unpackArgument = reset($args);
        try {
            return run_hook($name, $arg, $unpackArgument === true);
        } catch (Throwable $e) {
            Logger::error($e, [
                'status' => 'error',
                'type' => 'HookDispatcher',
                'method' => 'run',
                'name' => $name,
                'arg' => $arg,
                'args' => $args,
            ]);
            return $arg;
        }
    }
}
