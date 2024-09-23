<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractPlugin;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherHandlerInterface;
use Pentagonal\Neon\WHMCS\Addon\Http\RequestResponseExceptions\NotFoundException;

class PluginDispatcher implements DispatcherHandlerInterface
{
    /**
     * @var AbstractPlugin $plugin
     */
    protected AbstractPlugin $plugin;

    /**
     * PluginDispatcher constructor
     *
     * @param AbstractPlugin $plugin
     */
    public function __construct(AbstractPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @inheritDoc
     */
    public function isProcessable($vars, AdminDispatcherHandler $dispatcherHandler): bool
    {
        // not allowed if not enabled
        if (!$this->plugin->getSchema()->isEnableAdminPage()) {
            return false;
        }
        return $dispatcherHandler->getAdminDispatcher()->isApiRequest()
            ? $this->plugin->isApiEnabled()
            : $this->plugin->isPageAddonEnabled();
    }

    /**
     * @inheritDoc
     */
    public function process($vars, AdminDispatcherHandler $dispatcherHandler)
    {
        if (!$this->isProcessable($vars, $dispatcherHandler)) {
            throw new NotFoundException(
                $dispatcherHandler->getAdminDispatcher()->getCore()->getRequest()
            );
        }
        return $dispatcherHandler->getAdminDispatcher()->isApiRequest()
            ? $this->plugin->getApiOutput($vars, $dispatcherHandler)
            : $this->plugin->getAddonPageOutput($vars, $dispatcherHandler);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->plugin->getSchema()->getName();
    }

    /**
     * @inheritDoc
     */
    public function getPath(): ?string
    {
        return $this->plugin->getPlugins()->getPluginPathHash($this->plugin);
    }

    /**
     * @inheritDoc
     */
    public function isCaseSensitivePath(): bool
    {
        return true;
    }
}
