<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\SchemaModel;

use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\PluginSchemaInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\SchemasInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Plugin;
use Pentagonal\Neon\WHMCS\Addon\Schema\Traits\SchemaTrait;
use function array_map;
use function array_search;
use function array_unique;
use function array_values;
use function dirname;
use function file_exists;
use function realpath;
use const DIRECTORY_SEPARATOR;

class PluginSchema implements PluginSchemaInterface
{
    use SchemaTrait {
        __construct as private construct;
    }

    /**
     * @var array<string> $pluginDirectories the pluginDirectories
     */
    private array $pluginDirectories;

    public function __construct(SchemasInterface $schemas, string ...$pluginsDirectories)
    {
        $this->pluginDirectories = array_map(static function ($dir) {
            return realpath($dir)?:$dir;
        }, $pluginsDirectories);
        $this->pluginDirectories  = array_values(array_unique($this->pluginDirectories));
        $this->construct($schemas);
    }

    /**
     * @inheritDoc
     */
    public function removePluginDirectory(string $pluginDirectory): void
    {
        $pluginDirectory = realpath($pluginDirectory)?:$pluginDirectory;
        $key = array_search($pluginDirectory, $this->getPluginDirectories());
        if ($key !== false) {
            unset($this->pluginDirectories[$key]);
            $this->pluginDirectories = array_values($this->pluginDirectories);
        }
    }

    /**
     * @inheritDoc
     */
    public function addPluginsDirectory(string $pluginDirectory): void
    {
        $pluginDirectory = realpath($pluginDirectory)?:$pluginDirectory;
        $this->removePluginDirectory($pluginDirectory);
        $this->pluginDirectories[] = $pluginDirectory;
    }

    /**
     * @inheritDoc
     */
    public function getPluginDirectories(): array
    {
        return $this->pluginDirectories;
    }

    /**
     * @inheritDoc
     */

    public function getSchemaFile(): string
    {
        // TODO: Implement getSchemaFile() method.
    }

    /**
     * @inheritDoc
     */

    public function getSchemaList(): array
    {
        // TODO: Implement getSchemaList() method.
    }

    /**
     * @inheritDoc
     */

    public function setActive(Plugin $plugin): bool
    {
        // TODO: Implement setActive() method.
    }

    /**
     * @inheritDoc
     */

    public function setInactive(Plugin $plugin): bool
    {
        // TODO: Implement setInactive() method.
    }

    /**
     * @inheritDoc
     */

    public function isActive(Plugin $plugin): bool
    {
        // TODO: Implement isActive() method.
    }

    /**
     * @inheritDoc
     */

    public function loadPlugin(Plugin $plugin): bool
    {
        // TODO: Implement loadPlugin() method.
    }

    /**
     * @inheritDoc
     */

    public function getRefSchemaFile(): string
    {
        $file = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'schema' . DIRECTORY_SEPARATOR . 'plugin.json';
        return $this->refSchemaFile ??= file_exists($file)
            ? (realpath($file)?:$file)
            :  Plugin::ID;
    }

    /**
     * @inheritDoc
     */

    public function jsonSerialize()
    {
        // todo: Implement jsonSerialize() method.
    }
}
