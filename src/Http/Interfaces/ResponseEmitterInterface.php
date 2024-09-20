<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Http\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface ResponseEmitterInterface
{
    /**
     * Emit the response
     *
     * @param ResponseInterface $response
     * @param bool $reduceError
     * @return mixed
     */
    public function emit(ResponseInterface $response, bool $reduceError = false);

    /**
     * Close the http
     */
    public function close();

    /**
     * Get emit count
     *
     * @return int
     */
    public function getEmitCount() : int;

    /**
     * Is Emitted
     *
     * @return bool
     */
    public function emitted() : bool;

    /**
     * Is closed
     *
     * @return bool
     */
    public function isClosed() : bool;
}
