<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Random;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\EventManagerInterface;
use Throwable;
use function array_pop;
use function array_search;
use function count;
use function end;
use function get_object_vars;
use function gettype;
use function in_array;
use function sprintf;

/**
 * Event Manager
 * The class to manage the event
 *
 * @template TName of string
 * @template TFunction of callable
 * @template TParam of mixed
 */
class EventManager implements EventManagerInterface
{
    /**
     * @var Collector<TName, Collector<array{0: bool, 1: TFunction>> $listeners the listeners
     */
    private Collector $listeners;

    /**
     * @var array{0: TName, 1: TFunction} $current current event
     */
    private array $current = [];

    /**
     * @var array{0: TName, 1: TFunction[]}> $processing current processing events
     */
    private array $processing = [];

    /**
     * @var array<TName, TParam[]> $originalParams list of dispatched params
     */
    private array $originalParams = [];

    /**
     * EventManager constructor.
     */
    public function __construct()
    {
        $this->listeners = new Collector();
    }

    /**
     * @inheritDoc
     */
    public function attach(string $name, callable $eventCallback, bool $once = false)
    {
        if (!isset($this->listeners[$name])) {
            $this->listeners[$name] = new Collector();
        }
        $this->listeners[$name]->push([
            $once,
            $eventCallback
        ]);
    }

    /**
     * @inheritDoc
     */
    public function is(string $name, ?callable $eventCallback = null): bool
    {
        if ($this->currentEventName() !== $name) {
            return false;
        }
        return !$eventCallback || ($this->current[1] ?? null) === $eventCallback;
    }

    /**
     * @inheritDoc
     */
    public function currentEventName(): ?string
    {
        return $this->current[0] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentParam()
    {
        $currentEventName = $this->currentEventName();
        if ($currentEventName === null || !isset($this->originalParams[$currentEventName])) {
            return null;
        }
        return end($this->originalParams[$currentEventName]);
    }

    /**
     * @inheritDoc
     */
    public function apply(string $name, $param = null, ...$args)
    {
        if (!isset($this->listeners[$name])) {
            return $param;
        }

        if (count($this->listeners[$name]) === 0) {
            unset($this->listeners[$name]);
            return $param;
        }
        $stopCode = Random::bytes();
        $performance = Performance::profile('apply', 'system.event_manager')
            ->setStopCode($stopCode)
            ->setData([
                'event' => $name
            ]);
        try {
            if (!isset($this->originalParams[$name])) {
                $this->originalParams[$name] = [];
            }

            /**
             * @var Collector<array<bool, callable>> $listeners
             */
            $listeners = $this->listeners[$name];
            $listeners->rewind();
            $this->originalParams[$name][] = $param;
            do {
                $current = $listeners->current();
                if ($current === false) {
                    break;
                }
                $index = $listeners->key();
                /**
                 * @var callable $callback
                 * @var bool $once
                 */
                $callback = $current[1];
                $once = $current[0];
                if ($this->in($name, $callback)) { // skip
                    continue;
                }
                if ($once) {
                    unset($listeners[$index]);
                }
                $this->current = [$name, $callback];
                $this->processing[$name][$index] = $callback;
                $applyPerformance = Performance::profile('apply_call', 'system.event_manager')
                    ->setStopCode($stopCode)
                    ->setData([
                        'event' => $name,
                        'callback' => $callback
                    ]);
                try {
                    $param = $callback($param, ...$args);
                } catch (Throwable $e) {
                    Logger::error($e, [
                        'type' => 'event',
                        'method' => 'apply',
                        'event' => $name,
                        'callback' => $callback
                    ]);
                    continue;
                } finally {
                    // detach the callback
                    $index = array_search($callback, $this->processing[$name], true);
                    if ($index !== false) {
                        unset($this->processing[$name][$index]);
                    }
                    $this->current = [];
                    $applyPerformance->stop([], $stopCode);
                }
            } while ($listeners->next() !== false);

            if (count($listeners[$name]) === 0) {
                unset($listeners[$name]);
            }
            if (isset($this->originalParams[$name])) {
                array_pop($this->originalParams[$name]);
                if (count($this->originalParams[$name]) === 0) {
                    unset($this->originalParams[$name]);
                }
            }
        } finally {
            $performance->stop([], $stopCode);
        }
        return $param;
    }

    /**
     * @inheritDoc
     */
    public function in(string $name, ?callable $eventCallback = null): bool
    {
        if (!isset($this->processing[$name])) {
            return false;
        }
        return $eventCallback === null || in_array($eventCallback, $this->processing[$name], true);
    }

    /**
     * @inheritDoc
     */
    public function detach(string $name, ?callable $eventCallback = null): int
    {
        $performance = Performance::profile('detach', 'system.event_manager')
            ->setData([
                'event' => $name,
                'callback' => $eventCallback
            ]);
        $result = 0;
        if (isset($this->listeners[$name])) {
            if ($eventCallback === null) {
                $result = count($this->listeners[$name]);
                unset($this->listeners[$name]);
            } else {
                foreach ($this->listeners[$name] as $index => $listener) {
                    if ($listener[1] === $eventCallback) {
                        unset($this->listeners[$name][$index]);
                        $result++;
                    }
                    if (count($this->listeners[$name]) === 0) {
                        unset($this->listeners[$name]);
                        break;
                    }
                }
            }
        }
        $performance->stop([
            'count' => $result
        ]);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->listeners->count();
    }

    /**
     * Debug info for var_dump / print_r
     *
     * @return array
     */
    public function __debugInfo() : array
    {
        $vars = get_object_vars($this);
        foreach ($vars['originalParams'] as $key => $value) {
            foreach ($value as $k => $v) {
                $vars['originalParams'][$key][$k] = sprintf('<redacted> type:%s', gettype($v));
            }
        }
        foreach ($vars['processing'] as $key => $value) {
            $vars['processing'][$key] = sprintf('<redacted> count:%d', count($value));
        }
        if (!empty($vars['current'])) {
            $vars['current'] = sprintf('<redacted> event:%s', $vars['current'][0]);
        }
        return $vars;
    }
}
