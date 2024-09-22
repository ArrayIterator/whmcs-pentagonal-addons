<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

use Psr\Http\Message\ServerRequestInterface;

interface HttpExceptionInterface extends ThrowableInterface
{
    /**
     * Get request
     *
     * @return ServerRequestInterface
     */
    public function getRequest() : ServerRequestInterface;
}
