<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

interface LogWriterInterface
{
    /**
     * @see \Monolog\Logger::$levels
     */
    public function write(array $record): void;
}
