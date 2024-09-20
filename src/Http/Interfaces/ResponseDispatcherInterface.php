<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Http\Interfaces;

use Psr\Http\Message\ResponseInterface;

/**
 * Response dispatcher
 */
interface ResponseDispatcherInterface
{
    /**
     * Dispatch the response
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatchResponse(ResponseInterface $response): ResponseInterface;
}
