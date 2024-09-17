<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Abstracts;

use Pentagonal\Neon\WHMCS\Addon\Interfaces\RepeatableServiceInterface;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\RunnableServiceInterface;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\ServiceInterface;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\ServicesInterface;
use function get_class;
use function in_array;
use function is_string;
use function strrpos;
use function strtolower;
use function substr;
use function trim;

/**
 * Base class for services
 * @abstract
 */
abstract class AbstractService implements ServiceInterface
{
    /**
     * @var string $name the service friendly name
     */
    protected $name;

    /**
     * @var string $category the service category
     */
    protected $category = 'utility';

    /**
     * @var string $description the service description
     */
    protected $description = '';

    /**
     * @var ServicesInterface $services
     */
    protected $services;

    /**
     * @var bool $hasRun
     */
    private $hasRun = false;

    /**
     * @var mixed $result
     */
    private $result = null;

    /**
     * @InheritDoc
     */
    public function __construct(ServicesInterface $services)
    {
        $this->services = $services;
    }

    /**
     * @inheritDoc
     */
    public function getServices(): ServicesInterface
    {
        return $this->services;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        if (!is_string($this->name)) {
            $className = get_class($this);
            $name = substr($className, strrpos($className, '\\') + 1);
            $this->name = $name;
        }
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getCategory(): string
    {
        if (!is_string($this->category)) {
            $this->category = self::DEFAULT_CATEGORY;
        }
        if (!in_array($this->category, self::CATEGORIES)) {
            $this->category = strtolower(trim($this->category));
            $this->category = !in_array($this->category, self::CATEGORIES)
                ? self::DEFAULT_CATEGORY
                : $this->category;
        }
        return $this->category;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        if (!is_string($this->description)) {
            $this->description = '';
        }
        return $this->description;
    }

    /**
     * Dispatch the service
     *
     * @param ...$args
     * @return mixed
     * @see RunnableServiceInterface::run()
     */
    final public function run(...$args)
    {
        if ($this instanceof RepeatableServiceInterface
            || (!$this->hasRun && $this instanceof RunnableServiceInterface)
        ) {
            $this->hasRun = true;
            $this->result = $this->dispatch(...$args);
        }
        return $this->result;
    }

    /**
     * Method to dispatch the service when the service is runnable
     *
     * @param ...$args
     * @abstract
     */
    protected function dispatch(...$args)
    {
        return null;
    }
}
