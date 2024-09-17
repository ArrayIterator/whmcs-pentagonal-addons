<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces;

use Pentagonal\Neon\WHMCS\Addon\Dispatcher\AdminDispatcherHandler;

interface DispatcherHandlerInterface
{
    /**
     * Is processable
     *
     * @param $vars
     * @return bool
     */
    public function isProcessable($vars) : bool;

    /**
     * Process dispatcher
     *
     * @param $vars
     * @param AdminDispatcherHandler $dispatcherHandler
     * @return mixed
     */
    public function process($vars, AdminDispatcherHandler $dispatcherHandler);

    /**
     * Get page
     *
     * @return string
     */
    public function getPage() : string;

    /**
     * Make rule if it case-sensitive
     *
     * @return bool
     */
    public function isCaseSensitivePage() : bool;
}
