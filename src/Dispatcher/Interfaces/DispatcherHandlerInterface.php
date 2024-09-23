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
     * @param AdminDispatcherHandler $dispatcherHandler
     * @return bool
     */
    public function isProcessable($vars, AdminDispatcherHandler $dispatcherHandler) : bool;

    /**
     * Process dispatcher
     *
     * @param $vars
     * @param AdminDispatcherHandler $dispatcherHandler
     * @return mixed|string|object
     */
    public function process($vars, AdminDispatcherHandler $dispatcherHandler);

    /**
     * @return string
     */
    public function getName() : string;

    /**
     * Get page
     *
     * @return ?string
     */
    public function getPath() : ?string;

    /**
     * Make rule if it case-sensitive
     *
     * @return bool
     */
    public function isCaseSensitivePath() : bool;
}
