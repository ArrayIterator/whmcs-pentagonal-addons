<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Abstracts;

use Pentagonal\Neon\WHMCS\Addon\Interfaces\ExtendedInterface;
use RuntimeException;
use WHMCS\Application\Support\Facades\Di;
use function call_user_func_array;
use function is_object;
use function method_exists;
use function strtolower;

/**
 * Abstract Extended
 *
 * @method static mixed getFacadeApplication()
 * @method static mixed facadeApplication()
 * @method static string getFacadeName()
 * @method static string facadeName()
 * @method static mixed getAccessor()
 * @method static mixed accessor()
 */
abstract class AbstractExtended implements ExtendedInterface
{
    /**
     * @var static $instance the instance
     */
    protected static AbstractExtended $instance;

    /**
     * @var mixed $facadeApplication the facade application
     */
    protected $facadeApplication = null;

    /**
     * AbstractExtended constructor.
     */
    final protected function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return static::getInstance()->__call($name, $arguments);
    }

    /**
     * @inheritDoc
     */
    public function __call(string $name, array $arguments)
    {
        return $this->magicCaller($name, $arguments);
    }

    /**
     * Magic method to call the method
     *
     * @param string $name
     * @param array $arguments
     * @return mixed|null
     */
    protected function magicCaller(string $name, array $arguments)
    {
        switch (strtolower($name)) {
            case 'getfacadeapplication':
            case 'facadeapplication':
                return $this->getFacadeApplication();
            case 'getfacadename':
            case 'facadename':
                return $this->getFacadeName();
            case 'getaccessor':
            case 'accessor':
                return $this->getAccessor();
        }
        $accessor = $this->getAccessor();
        if (is_object($accessor) && method_exists($accessor, $name)) {
            return call_user_func_array([$accessor, $name], $arguments);
        }
        return null;
    }

    /**
     * Get application
     * @return mixed|null
     */
    protected function getFacadeApplication()
    {
        if (!$this->facadeApplication) {
            $facadeName = $this->getFacadeName();
            $this->facadeApplication = Di::getFacadeApplication()->has($facadeName)
                ? Di::getFacadeApplication()->get($facadeName)
                : false;
        }
        return $this->facadeApplication ?: null;
    }

    /**
     * The facade name
     *
     * @return string
     */
    abstract protected function getFacadeName(): string;

    /**
     * Get facade accessor
     */
    protected function getAccessor()
    {
        return $this->getFacadeApplication();
    }

    /**
     * @inheritDoc
     * @return static
     */
    public static function getInstance(): AbstractExtended
    {
        if (static::class === __CLASS__) {
            throw new RuntimeException('Cannot instantiate abstract class ' . __CLASS__);
        }
        return static::$instance ??= new static();
    }
}
