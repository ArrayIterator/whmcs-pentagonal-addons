<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

interface ExtendedInterface
{
    /**
     * Get extended instance
     *
     * @return static
     */
    public static function getInstance(): ExtendedInterface;

    /**
     * Magic method to call the method statically
     *
     * @return string
     */
    public static function __callStatic(string $name, array $arguments);

    /**
     * Magic method to call the method
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments);
}
