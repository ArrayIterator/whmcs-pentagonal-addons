<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Services;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractService;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\RunnableServiceInterface;

/**
 * Class AdminService for admin service handler
 */
class AdminService extends AbstractService implements RunnableServiceInterface
{
    /**
     * @var string $name the service friendly name
     */
    protected string $name = 'Admin Service';

    /**
     * @var string $category the service category
     */
    protected string $category = 'system';

    /**
     * @inheritDoc
     */
    protected function dispatch($arg = null, ...$args)
    {
        $core = $this->getServices()->getCore();
        if (!$core->getAddon()->isAddonPage()) {
            return;
        }
        $core->getAdminDispatcher()->dispatch();
    }
}
