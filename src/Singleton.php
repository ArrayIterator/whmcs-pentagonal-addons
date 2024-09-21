<?php
/**
 * @noinspection PhpMissingFieldTypeInspection
 * @noinspection PhpMissingReturnTypeInspection
 */
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon;

use Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Swaggest\JsonSchema\Schema;
use function class_exists;
use function defined;
use function dirname;
use function extension_loaded;
use function file_exists;
use function function_exists;
use function interface_exists;
use function spl_autoload_register;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use const DIRECTORY_SEPARATOR;

/**
 * Compat
 */
final class Singleton
{
    /**
     * @var self $instance the singleton instance
     */
    private static $instance;

    /**
     * @var bool $readyToLoad determine dependencies ready to load
     */
    private $readyToLoad;

    /**
     * @var null|false|Core
     */
    private $core;

    /**
     * @var class-string[] existences of classes
     */
    private $classesToCheck = [
        Schema::class,
    ];

    /**
     * @var string[] $extensionsToCheck extensions list
     */
    private $extensionsToCheck = [
        'Ioncube Loader',
        'json',
        'curl'
    ];

    /**
     * @var class-string[] $interfacesToCheck
     */
    private $interfacesToCheck = [
        ResponseFactoryInterface::class,
        ServerRequestInterface::class,
        UriInterface::class
    ];

    /**
     * @private
     */
    private function __construct()
    {
        $this->initAutoload();
    }

    /**
     * Init the-autoload
     *
     * @return void
     */
    private function initAutoload()
    {
        $this->readyToLoad = false;
        if (PHP_VERSION_ID < 70400
            || !function_exists('add_hook')
            || ! defined('WHMCS')
            || !file_exists(dirname(__DIR__) .'/vendor/autoload.php')) {
            return;
        }

        try {
            require dirname(__DIR__) .'/vendor/autoload.php';
        } catch (Exception $e) {
            return;
        }

        foreach ($this->interfacesToCheck as $interface) {
            if (!interface_exists($interface)) {
                return;
            }
        }
        foreach ($this->classesToCheck as $class) {
            if (!class_exists($class)) {
                return;
            }
        }
        foreach ($this->extensionsToCheck as $extension) {
            if (!extension_loaded($extension)) {
                return;
            }
        }
        $this->readyToLoad = true;
        if (class_exists(Core::class)) {
            return;
        }
        spl_autoload_register(static function ($className) {
            $namespace = __NAMESPACE__ . '\\';
            $directory = __DIR__ . DIRECTORY_SEPARATOR;
            if (strpos($className, $namespace) !== 0) {
                return;
            }
            $className = substr($className, strlen($namespace));
            $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
            $file = $directory . $className . '.php';
            if (file_exists($file)) {
                require $file;
            }
        });
        $this->readyToLoad = class_exists(Core::class);
    }

    /**
     * @return ?Core instance of core
     */
    public function getCore()
    {
        if (isset($this->core)) {
            return $this->core?:null;
        }
        $this->core = false;
        if ($this->readyToLoad) {
            $this->core = Core::createInstance();
        }
        return $this->core?:null;
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return ?Core
     */
    public static function core()
    {
        return self::getInstance()->getCore();
    }

    /**
     * @return ?Core
     */
    public static function dispatch()
    {
        $core = self::core();
        if ($core) {
            return $core->isDispatched() ? $core : $core->dispatch();
        }
        return $core;
    }
}
