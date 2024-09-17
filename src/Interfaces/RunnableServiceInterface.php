<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

interface RunnableServiceInterface extends ServiceInterface
{
    /**
     * Dispatch the service
     */
    public function run(...$args);
}
