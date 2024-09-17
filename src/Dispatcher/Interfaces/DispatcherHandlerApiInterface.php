<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces;

use Pentagonal\Neon\WHMCS\Addon\Dispatcher\AdminDispatcherHandler;

interface DispatcherHandlerApiInterface extends DispatcherHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function process($vars, AdminDispatcherHandler $dispatcherHandler) : DispatcherResponseInterface;
}
