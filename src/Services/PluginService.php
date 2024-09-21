<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Services;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractService;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\RunnableServiceInterface;

/**
 * Plugin service loader
 * The plugin only load on addon page
 */
class PluginService extends AbstractService implements RunnableServiceInterface
{
    /**
     * Dispatch the service load the plugins
     *
     * @inheritDoc
     */
    protected function dispatch($arg = null, ...$args)
    {
        $core = $this->getServices()->getCore();
        if (!$core->getAddon()->isAddonPage()) {
            return;
        }
        $core->getPlugins()->load();
    }
}
