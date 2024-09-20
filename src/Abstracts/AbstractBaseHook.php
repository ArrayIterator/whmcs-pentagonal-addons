<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Abstracts;

use Pentagonal\Neon\WHMCS\Addon\Interfaces\HookInterface;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\HooksInterface;
use function array_filter;
use function array_unique;
use function get_class;
use function is_array;
use function is_string;
use function strrpos;
use function substr;

/**
 * Abstract base hook class
 * @abstract
 */
abstract class AbstractBaseHook implements HookInterface
{
    /**
     * @see HookInterface::HOOKS
     *
     * @var string[]|string $hooks the hook name
     */
    protected $hooks = [];

    /**
     * @var string $hookName The hook name
     */
    protected string $name;

    /**
     * @var int $priority The hook priority
     */
    protected int $priority = 10;

    /**
     * @var HooksInterface $hooksService The service
     */
    private HooksInterface $hooksService;

    /**
     * @var bool $dispatched The hook dispatched status
     */
    private bool $dispatched = false;

    /**
     * @var array|mixed $vars The hook vars
     */
    private $vars = null;

    /**
     * Hook constructor.
     *
     * @param HooksInterface $hooks
     */
    public function __construct(HooksInterface $hooks)
    {
        $this->hooksService = $hooks;
    }

    /**
     * Get the service
     *
     * @return HooksInterface
     */
    public function getHooksService(): HooksInterface
    {
        return $this->hooksService;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        if (!isset($this->name)) {
            $className = get_class($this);
            $this->name = substr($className, strrpos($className, '\\') + 1);
        }
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getHooks(): array
    {
        if (is_string($this->hooks)) {
            $this->hooks = [$this->hooks];
        }
        if (!is_array($this->hooks)) {
            $this->hooks = [];
        }
        $this->hooks = array_unique(array_filter($this->hooks, 'is_string'));
        return $this->hooks;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get the initial vars
     *
     * @return mixed|array
     */
    public function getInitVars()
    {
        return $this->vars;
    }

    /**
     * @inheritDoc
     */
    final public function run($vars = null)
    {
        if ($this->dispatched) {
            return $vars;
        }
        $this->vars = $vars;
        $this->dispatched = true;
        $newVars = $this->dispatch($vars);
        if ($newVars !== null) {
            $vars = $newVars;
        }
        return $vars;
    }

    /**
     * Dispatch the hook
     *
     * @param $vars
     * @abstract
     * @return mixed|array
     * @noinspection PhpInconsistentReturnPointsInspection
     * @template-implements \Pentagonal\Neon\WHMCS\Addon\Interfaces\RunnableServiceInterface
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    protected function dispatch($vars)
    {
        // implement this method
    }
}
