<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

use Pentagonal\Neon\WHMCS\Addon\Plugins;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Plugin;
use Throwable;

interface PluginInterface
{
    /**
     * PluginInterface constructor.
     * @param Plugins $plugins
     * @param Plugin $schema
     * @throws \Pentagonal\Neon\WHMCS\Addon\Exceptions\UnsupportedArgumentDataTypeException
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function __construct(Plugins $plugins, Plugin $schema);

    /**
     * Get plugin schema
     *
     * @return ?Plugin
     */
    public function getSchema() : Plugin;

    /**
     * Get load error
     *
     * @return ?Throwable
     */
    public function getLoadError(): ?Throwable;

    /**
     * Get plugins
     *
     * @return Plugins
     */
    public function getPlugins(): Plugins;

    /**
     * Check if plugin loaded
     *
     * @return bool
     */
    public function isLoaded() : bool;

    /**
     * Load the plugin
     * @return void
     */
    public function load(): void;
}
