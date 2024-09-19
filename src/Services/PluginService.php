<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Services;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractService;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\RunnableServiceInterface;
use SplObjectStorage;

/**
 * Plugin service loader
 * The plugin only load on addon page
 */
class PluginService extends AbstractService implements RunnableServiceInterface
{
    /**
     * @var ?SplObjectStorage $plugins The plugin storage
     */
    protected ?SplObjectStorage $plugins = null;

    /**
     * Dispatch the service load the plugins
     *
     * @inheritDoc
     */
    protected function dispatch($arg = null, ...$args)
    {
        // to do read plugin
        $addon = $this->getServices()->getCore()->getAddon();
        if (!$addon->isAddonPage() || !$addon->isAllowedAccessAddonPage()) {
            return;
        }
        // todo load plugin
    }
}
