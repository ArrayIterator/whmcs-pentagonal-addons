<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Http\Interfaces;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface HttpExceptionInterface extends Throwable
{
    /**
     * Get request
     *
     * @return ServerRequestInterface
     */
    public function getRequest() : ServerRequestInterface;
}
