<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Services;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractBaseHook;
use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractService;
use Pentagonal\Neon\WHMCS\Addon\Helpers\HookInclude;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Hooks\AdminAreaMenuButton;
use Pentagonal\Neon\WHMCS\Addon\Hooks\AdminAreaHeadOutput;
use Pentagonal\Neon\WHMCS\Addon\Hooks\VersionHook;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\HookInterface;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\HooksServiceInterface;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\ServicesInterface;
use Pentagonal\Neon\WHMCS\Addon\Libraries\HookDispatcher;
use ReflectionClass;
use Throwable;
use WHMCS\View\Template\Theme;
use function array_shift;
use function call_user_func;
use function count;
use function function_exists;
use function get_class;
use function hook_log;
use function in_array;
use function is_array;
use function is_callable;
use function is_dir;
use function is_object;
use function is_string;
use function strtolower;
use function trim;

class Hooks extends AbstractService implements HooksServiceInterface
{
    /**
     * @var string EVENT_BEFORE_HOOKS_INIT The before hooks init event
     */
    public const EVENT_BEFORE_HOOKS_INIT = 'HooksBeforeInit';

    /**
     * @var string EVENT_AFTER_HOOKS_INIT The after hooks init event
     */
    public const EVENT_AFTER_HOOKS_INIT = 'HooksAfterInit';

    /**
     * @var class-string<HookInterface>[] $hookFactories The hook factories
     */
    public const HOOK_FACTORIES = [
        VersionHook::class,
        AdminAreaHeadOutput::class,
        AdminAreaMenuButton::class,
    ];

    /**
     * @var array<string, class-string<HookInterface|false> $cachedClass The cached class
     */
    private static $cachedClass = [];

    /**
     * @var string $name the hook name
     */
    protected $name = 'Hooks';

    /**
     * @var array<HookInterface> $queue The hook queue
     */
    protected $queue = [];

    /**
     * @var array<array<string, bool>> $dispatched The dispatched hook
     */
    protected $dispatched = [];

    /**
     * @var \Pentagonal\Neon\WHMCS\Addon\Interfaces\HookDispatcherInterface $dispatcher
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    private $dispatcher;

    /**
     * @var bool $inQueue The hook in queue status
     */
    private $inQueue = false;

    /**
     * @var bool $initialized is initialized
     */
    private $initialized = false;

    /**
     * @inheritDoc
     */
    public function queued($hook): bool
    {
        $className = $this->getHookClassName($hook);
        return $className && isset($this->queue[$className]);
    }

    /**
     * Get the hook class name
     *
     * @template TClass HookInterface
     * @param class-string<TClass>|TClass|mixed $param
     * @return ?class-string<TClass>
     */
    final public function getHookClassName($param): ?string
    {
        if (is_object($param)) {
            return $param instanceof HookInterface ? get_class($param) : null;
        }
        /** @noinspection DuplicatedCode */
        if (!is_string($param)) {
            return null;
        }
        $param = trim($param);
        if (!$param) {
            return null;
        }

        // clear cache
        while (count(self::$cachedClass) > 1000) {
            array_shift(self::$cachedClass);
        }

        $lower = strtolower($param);
        $className = self::$cachedClass[$lower] ?? null;
        if ($className !== null) {
            return $className ?: null;
        }
        self::$cachedClass[$lower] = false;
        try {
            $ref = new ReflectionClass($param);
            $className = $ref->getName();
            $lowerClassName = strtolower($className);
            self::$cachedClass[$lowerClassName] = false;
            if (!$ref->implementsInterface(HookInterface::class)) {
                return null;
            }
            self::$cachedClass[strtolower($className)] = $className;
            self::$cachedClass[$lower] = $className;
        } catch (Throwable $e) {
            return null;
        }
        return $className;
    }

    /**
     * @inheritDoc
     */
    public function dispatched($hook): bool
    {
        $className = $this->getHookClassName($hook);
        return $className && isset($this->dispatched[$className]);
    }

    /**
     * @inheritDoc
     */
    public function getQueued(): array
    {
        return $this->queue;
    }

    /**
     * @inheritDoc
     */
    public function containHook(string $hookName): bool
    {
        foreach ($this->dispatched as $hooks) {
            if (isset($hooks[$hookName])) {
                return true;
            }
        }
        foreach ($this->queue as $item) {
            if (in_array($hookName, $item->getHooks())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create dynamic hooks
     *
     * @param string $hookName
     * @param callable $callback
     * @param int $priority
     * @param string|null $name
     * @return HookInterface
     */
    public function createDynamicHook(
        string   $hookName,
        callable $callback,
        int      $priority = 10,
        ?string  $name = null
    ): HookInterface {
        return new class(
            $this,
            $hookName,
            $callback,
            $priority,
            $name
        ) extends AbstractBaseHook {

            /**
             * @var callable $callback
             */
            protected $callback;

            /**
             * The dynamic hook constructor.
             *
             * @param HooksServiceInterface $hooks
             * @param string|null $hook
             * @param callable|null $callback
             * @param int $priority
             * @param string|null $name
             */
            public function __construct(
                HooksServiceInterface $hooks,
                string                $hook = null,
                callable              $callback = null,
                int                   $priority = 10,
                ?string               $name = null
            ) {
                parent::__construct($hooks);
                $this->hooks = $hook;
                $this->priority = $priority;
                $this->callback = $callback;
                if (!is_string($name)) {
                    $name = 'Dynamic Hook: ' . $hook;
                }
                $this->name = $name;
            }

            /**
             * @inheritDoc
             */
            protected function dispatch($vars)
            {
                if (!is_callable($this->callback)) {
                    return $vars;
                }
                return call_user_func($this->callback, $vars);
            }
        };
    }

    /**
     * @inheritDoc
     */
    final public function __construct(ServicesInterface $services)
    {
        parent::__construct($services);
        $this->dispatcher = new HookDispatcher();
        foreach (self::HOOK_FACTORIES as $hook) {
            $this->queue($hook);
        }
    }

    /**
     * @inheritDoc
     */
    public function queue($hook): bool
    {
        $className = $this->getHookClassName($hook);
        if (!$className || isset($this->dispatched[$className])) {
            return false;
        }
        if (is_string($hook)) {
            return $this->queue(new $className($this));
        }
        $this->queue[$className] = $hook;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(...$args)
    {
        $this->init();
        // prevent multiple queue
        if ($this->inQueue) {
            return;
        }
        $this->inQueue = true;
        try {
            while (count($this->queue) > 0) {
                $hook = array_shift($this->queue);
                if (!$hook instanceof HookInterface) {
                    continue;
                }
                $className = get_class($hook);
                foreach ($hook->getHooks() as $hookName) {
                    if (!is_string($hookName)) {
                        continue;
                    }
                    $this->dispatched[$className][$hookName] = true;
                    $this->dispatcher->add($hookName, static function ($vars) use ($hook) {
                        $vars = is_array($vars) ? $vars : [];
                        return $hook->run($vars);
                    });
                }
            }
        } finally {
            $this->inQueue = false;
        }
    }

    /**
     * Initialize
     *
     * @return void
     * @private
     */
    private function init(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        $em = $this->services->getCore()->getEventManager();
        try {
            $em->apply(self::EVENT_BEFORE_HOOKS_INIT, $this);
            $theme = $this->services->getCore()->getTheme();
            if (!$theme instanceof Theme) {
                return;
            }
            $activeTemplate = $theme->getTemplatePath();
            if (!is_dir($activeTemplate)) {
                return;
            }
            try {
                HookInclude::include($this, $activeTemplate . '/hooks.php');
            } catch (Throwable $e) {
                Logger::error($e, [
                    'type' => 'Hook',
                    'method' => 'init',
                    'file' => $activeTemplate . '/hooks.php'
                ]);
                if (function_exists('hook_log')) {
                    hook_log('Pentagonal', 'Error Template Hook: ' . $e->getMessage());
                }
            }
        } finally {
            $em->apply(self::EVENT_AFTER_HOOKS_INIT, $this);
        }
    }
}
