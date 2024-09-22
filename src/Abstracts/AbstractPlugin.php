<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Abstracts;

use Pentagonal\Neon\WHMCS\Addon\Exceptions\InvalidArgumentCriteriaException;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\UnprocessableException;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\UnsupportedArgumentDataTypeException;
use Pentagonal\Neon\WHMCS\Addon\Helpers\DataNormalizer;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Random;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\EventManagerInterface;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\PluginInterface;
use Pentagonal\Neon\WHMCS\Addon\Plugins;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Plugin;
use Psr\Http\Message\UriInterface;
use ReflectionClass;
use ReflectionObject;
use Throwable;
use function array_pop;
use function explode;
use function get_class;
use function implode;
use function strtolower;

abstract class AbstractPlugin implements PluginInterface
{
    /**
     * @var bool $loaded
     */
    private bool $loaded = false;

    /**
     * @var bool $initLoaded Init Loaded
     */
    private bool $initLoaded = false;

    /**
     * @var Plugins $plugins
     */
    private Plugins $plugins;

    /**
     * @var ?Throwable
     */
    private ?Throwable $loadError = null;

    /**
     * @var Plugin $schema
     */
    private Plugin $schema;

    /**
     * @var string $pluginDirectory the plugin directory
     */
    private string $pluginDirectory;

    /**
     * @var UriInterface $pluginUri the plugin URI
     */
    private UriInterface $pluginUri;

    /**
     * @inheritDoc
     */
    final public function __construct(Plugins $plugins, Plugin $schema, ?ReflectionClass $reflectionClass = null)
    {
        $pluginNamespace = strtolower(ltrim($schema->getNamespace(), '\\'));
        $className = get_class($this);
        $lowerClassName = strtolower($className);
        $namespace = explode('\\', $lowerClassName);
        array_pop($namespace);
        $namespace = implode('\\', $namespace);
        if ($namespace !== $pluginNamespace) {
            throw new UnsupportedArgumentDataTypeException('Schema namespace not equal plugin namespace');
        }
        if ($reflectionClass && $reflectionClass->getName() === $lowerClassName) {
            $fileName = $reflectionClass->getFileName();
        } else {
            $fileName = (new ReflectionObject($this))->getFileName();
        }
        if (!$fileName) {
            throw new InvalidArgumentCriteriaException(
                'The plugin does not have file name'
            );
        }
        $this->pluginDirectory = dirname($fileName);
        $this->schema = $schema;
        $this->plugins = $plugins;
    }

    /**
     * @return string
     */
    final public function getPluginDirectory(): string
    {
        return $this->pluginDirectory;
    }

    /**
     * @return Plugin
     */
    final public function getSchema(): Plugin
    {
        // prevent modification
        return clone $this->schema;
    }

    /**
     * @inheritDoc
     */
    public function getPlugins(): Plugins
    {
        return $this->plugins;
    }

    /**
     * @return UriInterface
     */
    final public function getPluginUri(): UriInterface
    {
        return $this->pluginUri ??= $this->getPlugins()->getCore()->getUrl()->getPluginUri($this);
    }

    /**
     * @return ?Throwable
     */
    final public function getLoadError(): ?Throwable
    {
        return $this->loadError;
    }

    /**
     * @return EventManagerInterface
     */
    final public function getEventManager() : EventManagerInterface
    {
        return $this->getPlugins()->getCore()->getEventManager();
    }

    /**
     * @inheritDoc
     */
    final public function load(): void
    {
        if ($this->isInitLoaded() // if already init
            || $this->isLoaded() // is loaded
            || ! $this->getPlugins()->getCore()->getAddon()->isAddonPage() // if is on addon deny
        ) {
            return;
        }
        if (!$this->getPlugins()->isAllowActivated($this)) {
            return;
        }
        $stopCode = Random::bytes();
        $performance = Performance::profile('load', 'plugin')
            ->setStopCode($stopCode);
        $this->initLoaded = true;
        DataNormalizer::bufferedCall(function () {
            try {
                $this->doLoad();
                $this->loaded = true;
            } catch (Throwable $e) {
                $this->loadError = $e;
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'type' => 'Plugin',
                        'method' => 'load',
                        'message' => 'Error Loading Plugin',
                        'plugin' => get_class($this),
                    ]
                );
            }
            try {
                $this->getEventManager()->apply(Plugins::EVENT_PLUGIN_LOADED, $this);
            } catch (Throwable $e) {
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'type' => 'Plugin',
                        'method' => 'load',
                        'event' => Plugins::EVENT_PLUGIN_LOADED,
                    ]
                );
            }
        });
        $performance->stop([], $stopCode);
    }

    /**
     * Do load the plugin
     */
    abstract protected function doLoad();

    /**
     * @return bool
     */
    final public function isInitLoaded(): bool
    {
        return $this->initLoaded;
    }

    /**
     * @inheritDoc
     */
    final public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * @return void
     * @abstract called when __destruct called
     */
    protected function doMagicDestruct()
    {
    }

    /**
     * @abstract called when __call called
     * @return mixed
     */
    protected function doMagicCall($name, $arguments)
    {
        return null;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    final public function __call($name, $arguments)
    {
        if (!$this->isLoaded()) {
            return $this->doMagicCall($name, $arguments);
        }
        return null; // no return
    }

    /**
     * Magic Destruct
     */
    final public function __destruct()
    {
        if ($this->isLoaded()) {
            $this->doMagicDestruct();
        }
    }

    /**
     * @throws UnprocessableException
     */
    final public function __sleep()
    {
        throw new UnprocessableException('Cannot serialize ' . get_class($this));
    }

    /**
     * @throws UnprocessableException
     */
    final public function __wakeup()
    {
        throw new UnprocessableException('Cannot unserialize ' . get_class($this));
    }
}
