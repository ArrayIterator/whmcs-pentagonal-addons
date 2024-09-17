<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use WHMCS\Application\Support\Facades\Config;
use WHMCS\Config\Application;
use function array_key_exists;

/**
 * Class Application Config for application config of WHMCS
 */
final class ApplicationConfig
{
    /**
     * Get whmcs application config
     *
     * @return Application
     */
    public static function configs() : Application
    {
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return Config::self();
    }

    /**
     * Get config value
     *
     * @param string $key
     * @param $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $config = self::configs()->getData();
        return array_key_exists($key, $config) ? $config[$key] : $default;
    }

    /**
     * Set config value
     *
     * @param string $key
     * @param $value
     */
    public static function set(string $key, $value) : void
    {
        self::configs()[$key] = $value;
    }
}
