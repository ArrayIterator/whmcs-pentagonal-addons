<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use IteratorAggregate;
use Pentagonal\Hub\Schema\Whmcs\Plugin;
use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractPlugin;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\InvalidArgumentCriteriaException;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\UnexpectedValueException;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\UnprocessableException;
use Pentagonal\Neon\WHMCS\Addon\Helpers\DataNormalizer;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Options;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Random;
use Pentagonal\Neon\WHMCS\Addon\Schema\SchemaModel\PluginSchema;
use ReflectionClass;
use RuntimeException;
use Throwable;
use Traversable;
use function class_exists;
use function dirname;
use function file_exists;
use function get_class;
use function glob;
use function is_array;
use function is_bool;
use function is_file;
use function is_int;
use function is_string;
use function md5;
use function realpath;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function time;
use function trim;
use function uasort;
use const DIRECTORY_SEPARATOR;
use const GLOB_ONLYDIR;
use const ROOTDIR;

/**
 * Class Plugins for Neon WHMCS Addon
 * @template-implements Traversable<AbstractPlugin>
 */
class Plugins implements IteratorAggregate
{
    /**
     * @var string EVENT_PLUGIN_PROCESS Event Plugin Process
     */
    public const EVENT_PLUGIN_PROCESS = 'PluginLoadProcess';

    /**
     * @var string EVENT_PLUGIN_LOADED Event Plugin Loaded
     */
    public const EVENT_PLUGIN_LOADED = 'PluginLoaded';

    /**
     * @var string EVENT_PLUGINS_LOADED Event Plugins Loaded
     */
    public const EVENT_PLUGINS_LOADED = 'PluginsLoaded';

    /**
     * @var Core $core
     */
    protected Core $core;

    /**
     * @var ArrayAccess<string, AbstractPlugin> $plugins Plugins
     */
    protected ArrayAccess $plugins;

    /**
     * @var ArrayAccess<string, string> $pluginPathHash the hash
     */
    protected ArrayAccess $pluginPathHash;

    /**
     * @var bool $isAllowedToLoad
     */
    private bool $isAllowedToLoad;

    /**
     * @var PluginSchema
     */
    private PluginSchema $schema;

    /**
     * @var array<string, array{
     *     "name": string,
     *     "class": string,
     *     "time": integer,
     * }> $activePlugins
     */
    private array $activePluginsOptions;

    /**
     * @var string|array|false|string[]
     */
    private string $rootDir;

    /**
     * @var array<string>
     */
    private array $pluginsDirectories;

    /**
     * @var array<string, Throwable>
     */
    private array $pluginsError = [];

    /**
     * @var array<string, Plugin>
     */
    private array $pluginSchemas = [];

    /**
     * @var bool $loaded is loaded
     */
    private bool $loaded = false;

    /**
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        $this->plugins = new ArrayObject();
        $this->pluginPathHash = new ArrayObject();
        $this->core = $core;
        $this->rootDir = DataNormalizer::makeUnixSeparator(ROOTDIR . '/');
        $pluginSchema = $this
            ->getCore()
            ->getSchemas()
            ->get(PluginSchema::class);
        $this->pluginsDirectories = [dirname(__DIR__) . DIRECTORY_SEPARATOR . 'plugins'];
        $this->schema = $pluginSchema;
        // add event on services run
        $core->getEventManager()->attach(Services::EVENT_AFTER_SERVICES_RUN, [$this, 'load'], true);
    }

    /**
     * @return array
     */
    public function getPluginsError(): array
    {
        return $this->pluginsError;
    }

    /**
     * @return array
     */
    public function getPluginsDirectories(): array
    {
        return $this->pluginsDirectories;
    }

    /**
     * @return array
     */
    public function getPluginSchemas(): array
    {
        return $this->pluginSchemas;
    }

    /**
     * @return PluginSchema
     */
    public function getSchema(): PluginSchema
    {
        return $this->schema;
    }

    /**
     * @return array
     */
    public function getActivePluginsOptions(): array
    {
        if (!isset($this->activePluginsOptions)) {
            $activePluginsOptions = Options::get('active_plugins', $exists);
            $update = !is_array($activePluginsOptions) || !$exists;
            $activePluginsOptions = !is_array($activePluginsOptions) ? [] : $activePluginsOptions;
            $time = time();
            $time2000 = 946684800; // 2020-01-01
            foreach ($activePluginsOptions as $key => $item) {
                if (!is_string($key) || !is_array($item)) {
                    unset($activePluginsOptions[$key]);
                    $update = true;
                    continue;
                }
                $pluginActivatedTime = $item['time']??null;
                $pluginName = $item['name']??null;
                $pluginClassName = $item['class']??null;
                if (!is_int($pluginActivatedTime)
                    || !is_string($pluginName) || !is_string($pluginClassName)
                    || $pluginActivatedTime > $time
                    || $pluginActivatedTime < $time2000
                ) {
                    unset($activePluginsOptions[$key]);
                    $update = true;
                }
            }
            $this->activePluginsOptions = $activePluginsOptions;
            if ($update) {
                Options::set('active_plugins', $activePluginsOptions);
            }
        }

        return $this->activePluginsOptions;
    }

    /**
     * @param AbstractPlugin $plugin
     * @return bool
     */
    public function isAllowActivated(AbstractPlugin $plugin) : bool
    {
        if (!$this->getCore()->getEventManager()->in(self::EVENT_PLUGIN_PROCESS)) {
            return false;
        }
        if (!str_starts_with($plugin->getPluginDirectory(), $this->rootDir)) {
            return false;
        }
        $pathOnly = $this->getPluginPath($plugin);
        if (!$pathOnly) {
            return false;
        }
        return isset($this->getActivePluginsOptions()[$pathOnly]) && !$plugin->getLoadError();
    }


    /**
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Create plugin by dir
     *
     * @param string $dir
     * @return AbstractPlugin
     * @throws InvalidArgumentCriteriaException|UnprocessableException|UnexpectedValueException
     */
    public function createPlugin(string $dir) : AbstractPlugin
    {
        $dir = rtrim(DataNormalizer::makeUnixSeparator($dir), '/');
        if (!str_starts_with($dir, $this->rootDir)) {
            throw new InvalidArgumentCriteriaException(
                'Directory is outside from ROOTDIR'
            );
        }
        $pathOnly = trim(substr($dir, strlen($this->rootDir)), '/');
        $this->pluginSchemas[$pathOnly] = $this->getSchema()->getSchemaPlugin($dir);
        if (!$this->pluginSchemas[$pathOnly] instanceof Plugin) {
            unset($this->pluginSchemas[$pathOnly]);
            throw new InvalidArgumentCriteriaException(
                'Invalid schema for plugin'
            );
        }
        $stopCode = Random::bytes();
        $performance = Performance::profile('create_plugin', 'system.plugins')
            ->setStopCode($stopCode);
        try {
            $pluginFile = $dir . '/Plugin.php';
            if (!file_exists($pluginFile) || !is_file($pluginFile)) {
                throw new UnprocessableException(
                    'Plugin.php does not exists in : ' . $dir
                );
            }
            $namespace = trim($this->pluginSchemas[$pathOnly]->getNamespace(), '\\');
            $className = $namespace . '\\Plugin';
            if (!class_exists($className)) {
                DataNormalizer::bufferedCall(static function ($pluginFile) {
                    require_once $pluginFile;
                }, $pluginFile);
            }
            if (!class_exists($className)) {
                throw new InvalidArgumentCriteriaException(
                    sprintf('Class %s does not exist', $className)
                );
            }
            $ref = new ReflectionClass($className);
            if (!$ref->isSubclassOf(AbstractPlugin::class)) {
                throw new InvalidArgumentCriteriaException(
                    sprintf(
                        'Class %s is not subclass of %s',
                        $ref->getName(),
                        AbstractPlugin::class
                    )
                );
            }
            $object = new $className($this, $this->pluginSchemas[$pathOnly], $ref);
        } finally {
            $performance->stop([], $stopCode);
        }
        if (!isset($object)) {
            throw new UnexpectedValueException(
                'Can not create plugin'
            );
        }

        return $object;
    }

    /**
     * Fall back to load
     *
     * @return void
     */
    public function load()
    {
        if (!$this->isAllowedToLoad() || $this->isLoaded()) {
            return;
        }
        $core = $this->getCore();
        if (!$core->getEventManager()->in(Services::EVENT_AFTER_SERVICES_RUN)) {
            return;
        }
        $em = $this->getCore()->getEventManager();
        // no injection
        if ($em->in(self::EVENT_PLUGIN_PROCESS)) {
            return;
        }
        $stopCode = Random::bytes();
        $performance = Performance::profile('load', 'system.plugins')
            ->setStopCode($stopCode);
        $this->loaded = true;
        $validSchema = [];
        $invalidSchema = [];
        foreach ($this->pluginsDirectories as $directory) {
            $directory = glob($directory . '/*', GLOB_ONLYDIR);
            foreach ($directory as $dir) {
                $dir = realpath($dir)?:$dir;
                $pathOnly = trim(substr($dir, strlen($this->rootDir)), '/');
                try {
                    if (!file_exists($dir .'/Plugin.php')
                        || !is_file($dir .'/Plugin.php')
                    ) {
                        throw new RuntimeException(
                            'Plugin.php does not exists in : ' . $dir
                        );
                    }
                    $plugin = $this->createPlugin($dir);
                    $this->add($plugin);
                    $validSchema[] = $pathOnly;
                } catch (Throwable $e) {
                    Logger::error(
                        $e,
                        [
                            'status' => 'error',
                            'type' => 'Plugins',
                            'method' => 'load',
                            'plugin' => $pathOnly,
                            'class' => __CLASS__
                        ]
                    );
                    $this->pluginsError[$pathOnly] = $e;
                    $invalidSchema[] = $pathOnly;
                }
            }
        }
        $activeOptions = $this->getActivePluginsOptions();
        uasort($activeOptions, function ($a, $b) {
            return $a['time'] <=> $b['time'];
        });
        $activePlugins = [];
        $update = false;
        foreach ($this->pluginSchemas as $pathOnly => $schema) {
            if (!isset($this->plugins[$pathOnly])) {
                continue;
            }
            if (!isset($activeOptions[$pathOnly])) {
                continue;
            }
            $name = $schema->getName();
            $activePlugins[$pathOnly] = $activeOptions[$pathOnly];
            if ($activeOptions[$pathOnly]['name'] !== $name) {
                $activeOptions[$pathOnly]['name'] = $name;
                $update = true;
            }
            $activePlugins[$pathOnly] = $activeOptions[$pathOnly];
        }
        uasort($activePlugins, function ($a, $b) {
            return $a['time'] <=> $b['time'];
        });
        $em->detach(self::EVENT_PLUGIN_PROCESS); // remove first
        foreach ($activePlugins as $pathOnly => $item) {
            $plugin = $this->plugins[$pathOnly]??null;
            if (!$plugin) {
                unset($activeOptions[$pathOnly]);
                continue;
            }
            $this->activePluginsOptions[$pathOnly] = [
                'name' => $plugin->getSchema()->getName(),
                'class' => get_class($plugin),
                'time' => time()
            ];
            $em->attach(self::EVENT_PLUGIN_PROCESS, function () use ($plugin, $pathOnly, &$update) {
                if (!isset($this->plugins[$pathOnly])) {
                    unset($this->activePluginsOptions[$pathOnly]);
                    return;
                }
                $plugin->load();
                if (!$this->isPluginActive($plugin)) {
                    unset($this->activePluginsOptions[$pathOnly]);
                    $update = true;
                    $this->pluginsError[$pathOnly] = $plugin->getLoadError()??new UnprocessableException(
                        'Plugin can not be loaded'
                    );
                }
            }, true);
        }
        // dispatch
        $em->apply(self::EVENT_PLUGIN_PROCESS, $this);
        $em->detach(self::EVENT_PLUGIN_PROCESS);
        if ($update) {
            Options::set('active_plugins', $this->activePluginsOptions);
        }

        try {
            $em->apply(self::EVENT_PLUGINS_LOADED, $this);
        } catch (Throwable $e) {
            Logger::error(
                $e,
                [
                    'status' => 'error',
                    'type' => 'Plugins',
                    'method' => 'load',
                    'event' => self::EVENT_PLUGINS_LOADED,
                    'class' => __CLASS__
                ]
            );
        }

        $performance->stop([
            'valid_schema' => $validSchema,
            'invalid_schema' => $invalidSchema
        ], $stopCode);
    }

    /**
     * @param AbstractPlugin ...$plugins
     * @return void
     */
    public function activate(AbstractPlugin ...$plugins)
    {
        $em = $this->getCore()->getEventManager();
        if ($em->in(self::EVENT_PLUGIN_PROCESS)) {
            return; // don't inject here when in process
        }

        $update = false;
        $process = false;
        $paths = [];
        $em->detach(self::EVENT_PLUGIN_PROCESS); // remove first
        foreach ($plugins as $plugin) {
            if ($plugin->isLoaded() || $plugin->isInitLoaded()) {
                continue;
            }
            $process = true;
            $pathOnly = $this->getPluginPath($plugin);
            if (!$pathOnly) {
                continue;
            }
            $hasActive = isset($this->activePluginsOptions[$pathOnly]);
            $this->activePluginsOptions[$pathOnly] = [
                'name' => $plugin->getSchema()->getName(),
                'class' => get_class($plugin),
                'time' => time()
            ];
            $paths[$pathOnly] = $pathOnly;
            $em->attach(
                self::EVENT_PLUGIN_PROCESS,
                function () use ($plugin, &$update, $pathOnly, &$paths, $hasActive) {
                    // if removed
                    if (!isset($this->plugins[$pathOnly])) {
                        unset($this->activePluginsOptions[$pathOnly]);
                        unset($paths[$pathOnly]);
                        return;
                    }
                    $this->plugins[$pathOnly] = $plugin;
                    unset($paths[$pathOnly]);
                    $plugin->load();
                    if (!$this->isPluginActive($plugin)) {
                        unset($this->activePluginsOptions[$pathOnly]);
                        if ($hasActive) {
                            $update = true;
                        }
                        $this->pluginsError[$pathOnly] = $plugin->getLoadError()??new RuntimeException(
                            'Plugin can not be loaded'
                        );
                        return;
                    }
                    $update = true;
                },
                true
            );
        }
        if ($process) {
            $em->apply(self::EVENT_PLUGIN_PROCESS);
            foreach ($paths as $path) {
                unset($this->activePluginsOptions[$path]);
                $update = true;
            }
            if ($update) {
                Options::set('active_plugins', $this->getActivePluginsOptions());
            }
        }
    }

    /**
     * Check is plugin active
     *
     * @param AbstractPlugin $plugin
     * @return bool
     */
    public function isPluginActive(AbstractPlugin $plugin) : bool
    {
        if ($plugin->getLoadError()) {
            return false;
        }
        $path = $this->getPluginPath($plugin);
        if (!$path) {
            return false;
        }
        if ($plugin->isLoaded()) {
            return isset($this->getActivePluginsOptions()[$path]);
        }
        if (!$plugin->isInitLoaded()) {
            return false;
        }
        return isset($this->getActivePluginsOptions()[$path]);
    }

    /**
     * @param AbstractPlugin $plugin
     * @return string
     */
    public function getPluginPath(AbstractPlugin $plugin) : ?string
    {
        return $this->getPath($plugin->getPluginDirectory());
    }

    /**
     * @param string $path
     * @return ?string
     */
    public function getPath(string $path) : ?string
    {
        $path = DataNormalizer::makeUnixSeparator(realpath($path)??$path);
        if (!str_starts_with($path, $this->rootDir)) {
            return null;
        }
        return trim(substr($path, strlen($this->rootDir)), '/');
    }

    /**
     * Check if allowed to load
     *
     * @return bool
     */
    public function isAllowedToLoad() : bool
    {
        if (is_bool($this->isAllowedToLoad??null)) {
            return $this->isAllowedToLoad;
        }
        $core = $this->getCore();
        // to do read plugin
        $addon = $core->getAddon();
        return $this->isAllowedToLoad = $addon->isAddonPage() && $addon->isAllowedAccessAddonPage();
    }

    /**
     * @return Core
     */
    public function getCore(): Core
    {
        return $this->core;
    }

    /**
     * @param AbstractPlugin $plugin
     * @return void
     */
    public function add(AbstractPlugin $plugin)
    {
        $path = $this->getPluginPath($plugin);
        if (!$path || isset($this->plugins[$path]) && $this->plugins[$path]->isLoaded()) {
            return;
        }
        $hash = $this->getPluginPathHash($plugin);
        $this->plugins[$path] = $plugin;
        $this->pluginPathHash[$hash] = $path;
    }

    /**
     * Get plugin path hash
     *
     * @param AbstractPlugin $plugin
     * @return string
     */
    public function getPluginPathHash(AbstractPlugin $plugin) : ?string
    {
        $path = $this->getPluginPath($plugin);
        if (!$path) {
            return null;
        }
        $hash = md5(get_class($plugin));
        $this->pluginPathHash[$hash] = $path;
        return $hash;
    }

    /**
     * Remove Plugin
     *
     * @param AbstractPlugin $plugin
     * @return void
     */
    public function remove(AbstractPlugin $plugin)
    {
        foreach ($this->plugins as $key => $item) {
            if ($plugin === $item) {
                if ($item->isLoaded()) {
                    return;
                }
                unset($this->plugins[$key]);
                break;
            }
        }
    }

    /**
     * Get plugin by hash
     *
     * @param string $hash
     * @return ?AbstractPlugin|
     */
    public function getPluginByHash(string $hash) : ?AbstractPlugin
    {
        $path = $this->pluginPathHash[$hash]??null;
        return $path ? ($this->plugins[$hash]??null) : null;
    }

    /**
     * @return array<AbstractPlugin>
     */
    public function getPlugins() : array
    {
        return $this->plugins->getArrayCopy();
    }

    /**
     * @return Traversable<AbstractPlugin>
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator(iterator_to_array($this->plugins));
    }
}
