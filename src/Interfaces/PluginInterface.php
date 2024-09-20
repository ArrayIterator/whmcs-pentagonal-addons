<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Plugin;

interface PluginInterface
{
    /**
     * PluginInterface constructor.
     * @param string $pluginDirectory The plugin directory
     */
    public function __construct(string $pluginDirectory);

    /**
     * @return ?Plugin The schema of the plugin, return null if invalid
     */
    public function getSchema(): ?Plugin;

    /**
     * Get the plugin directory
     *
     * @return string The plugin directory
     */
    public function getPluginDirectory(): string;

    /**
     * @return string|null The plugin file
     */
    public function getPluginFile(): ?string;

    /**
     * Check if the plugin is valid
     *
     * @return bool The plugin is valid
     */
    public function isValid() : bool;

    /**
     * Load the plugin
     * @return void
     */
    public function load(): void;
}
