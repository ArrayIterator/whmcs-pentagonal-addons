<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces;

use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Plugin;

interface PluginSchemaInterface extends JsonSchemaInterface
{
    /**
     * @param SchemasInterface $schemas
     * @param array<string> $pluginsDirectories
     */
    public function __construct(SchemasInterface $schemas, string ...$pluginsDirectories);

    /**
     * Remove plugin directory
     *
     * @param string $pluginDirectory
     * @return void
     */
    public function removePluginDirectory(string $pluginDirectory): void;

    /**
     * @param string $pluginDirectory
     * @return void
     */
    public function addPluginsDirectory(string $pluginDirectory): void;

    /**
     * @return array<string>
     */
    public function getPluginDirectories(): array;

    /**
     * @return array<Plugin>
     */
    public function getSchemaList(): array;

    /**
     * Set plugin active
     *
     * @param Plugin $plugin
     * @return bool true if success
     */
    public function setActive(Plugin $plugin) : bool;

    /**
     * Set plugin inactive
     *
     * @param Plugin $plugin
     * @return bool
     */
    public function setInactive(Plugin $plugin) : bool;

    /**
     * Check if plugin is active
     *
     * @param Plugin $plugin
     * @return bool
     */
    public function isActive(Plugin $plugin) : bool;

    /**
     * Load plugin
     *
     * @param Plugin $plugin
     * @return bool
     */
    public function loadPlugin(Plugin $plugin) : bool;
}
