<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use Pentagonal\Neon\WHMCS\Addon\Services\Hooks;
use function debug_backtrace;
use function file_exists;
use const DEBUG_BACKTRACE_IGNORE_ARGS;

class HookInclude
{
    /**
     * Include a file only if the caller is the Hooks class
     *
     * @param Hooks $hooks the hooks service
     * @param string $file the file to include
     * @noinspection PhpUnusedParameterInspection
     */
    public static function include(Hooks $hooks, string $file): void
    {
        // only include the file if the caller is the Hooks class
        if ((debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? null) !== Hooks::class) {
            return;
        }
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
