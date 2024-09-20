<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces;

use Pentagonal\Neon\WHMCS\Addon\Dispatcher\AdminDispatcherHandler;

interface DispatcherHandlerStringInterface extends DispatcherHandlerInterface
{
    /**
     * @inheritDoc
     * @return string
     */
    public function process($vars, AdminDispatcherHandler $dispatcherHandler) : string;
}
