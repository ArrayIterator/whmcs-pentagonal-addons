<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

use Pentagonal\Hub\Schema\Whmcs\Plugin;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\AdminDispatcherHandler;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherResponseInterface;
use Pentagonal\Neon\WHMCS\Addon\Plugins;
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

    /**
     * Is api enabled
     *
     * @return bool
     */
    public function isApiEnabled() : bool;

    /**
     * Check if enable addon / admin page
     *
     * @return bool
     */
    public function isPageAddonEnabled() : bool;

    /**
     * Page output
     */
    public function getAddonPageOutput($vars, AdminDispatcherHandler $dispatcherHandler) : DispatcherResponseInterface;

    /**
     * The api output
     */
    public function getApiOutput($vars, AdminDispatcherHandler $dispatcherHandler) : DispatcherResponseInterface;
}
