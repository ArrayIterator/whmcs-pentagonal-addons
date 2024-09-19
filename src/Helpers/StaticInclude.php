<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use function extract;
use const EXTR_SKIP;

class StaticInclude
{
    /**
     * Include a file and extract the given parameters
     *
     * @param string $file the file to include
     * @param array $extractedParams the parameters to extract
     * @return mixed|null
     */
    public static function include(string $file, array $extractedParams = [])
    {
        if (!file_exists($file)) {
            return null;
        }
        return (static function ($file, $extractedParams) {
            extract($extractedParams, EXTR_SKIP);
            unset($extractedParams);
            return require $file;
        })($file, $extractedParams);
    }
}
