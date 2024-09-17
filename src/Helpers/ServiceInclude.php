<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use Pentagonal\Neon\WHMCS\Addon\Libraries\Services;
use function debug_backtrace;
use function file_exists;
use const DEBUG_BACKTRACE_IGNORE_ARGS;

class ServiceInclude
{
    /**
     * Include a file only if the caller is the Services class
     *
     * @param Services $services the hooks service
     * @param string $file the file to include
     * @noinspection PhpUnusedParameterInspection
     */
    public static function include(Services $services, string $file): void
    {
        // only include the file if the caller is the Services class
        if ((debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? null) !== Services::class) {
            return;
        }
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
