<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces;

use Throwable;

interface DispatcherResponseInterface
{
    /**
     * HTTP Status code
     *
     * @return int
     */
    public function getStatusCode() : int;

    /**
     * Get data
     *
     * @return mixed
     */
    public function getData();

    /**
     * Get error if status code is not >= 400
     *
     * @return ?Throwable
     */
    public function getError() : ?Throwable;
}
