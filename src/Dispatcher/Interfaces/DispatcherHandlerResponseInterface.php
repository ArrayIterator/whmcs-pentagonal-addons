<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces;

use Pentagonal\Neon\WHMCS\Addon\Dispatcher\AdminDispatcherHandler;
use Psr\Http\Message\ResponseInterface;

interface DispatcherHandlerResponseInterface extends DispatcherHandlerInterface
{
    /**
     * @inheritDoc
     * @return ResponseInterface
     */
    public function process($vars, AdminDispatcherHandler $dispatcherHandler) : ResponseInterface;
}
