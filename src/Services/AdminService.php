<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Services;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractService;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\AdminDispatcher;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\RunnableServiceInterface;

class AdminService extends AbstractService implements RunnableServiceInterface
{
    /**
     * @var string $name the service friendly name
     */
    protected $name = 'Admin Service';

    /**
     * @var string $category the service category
     */
    protected $category = 'system';

    /**
     * @var AdminDispatcher $adminDispatcher the module page
     */
    protected $adminDispatcher = null;

    /**
     * Get the module page object
     *
     * @return AdminDispatcher
     */
    public function getAdminDispatcher(): AdminDispatcher
    {
        if (!$this->adminDispatcher instanceof AdminDispatcher) {
            $this->adminDispatcher = new AdminDispatcher($this);
        }
        return $this->adminDispatcher;
    }

    /**
     * Check if the current user is allowed to access the admin page
     *
     * @return bool
     */
    public function isAllowedAccessAddonPage(): bool
    {
        return $this->getServices()->getCore()->getAddon()->isAllowedAccessAddonPage();
    }

    /**
     * @inheritDoc
     */
    protected function dispatch(...$args)
    {
        if (!$this->getServices()->getCore()->isAdminAreaRequest()) {
            return;
        }
        $this->getAdminDispatcher()->dispatch();
    }
}
