<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces;

use Pentagonal\Neon\WHMCS\Addon\Interfaces\PluginInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Plugin;

interface PluginSchemaInterface extends JsonSchemaInterface
{
    /**
     * Get schema from given plugin directory
     *
     * @param string $pluginDirectory
     * @return Plugin
     */
    public function getSchemaPlugin(string $pluginDirectory) : Plugin;

    /**
     * Get schema from given plugin
     *
     * @param PluginInterface $plugin
     * @return ?Plugin
     */
    public function getSchema(PluginInterface $plugin): ?Plugin;
}
