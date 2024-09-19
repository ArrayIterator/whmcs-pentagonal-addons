<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use function array_key_exists;
use function is_array;

/**
 * Class Config for global config of WHMCS
 */
final class Config
{
    /**
     * @var null|array $originalConfig the original config
     */
    protected static ?array $originalConfig = null;

    /**
     * @var array|null $config the config
     */
    protected static ?array $config = null;

    /**
     * Get global config
     *
     * @return array<string, mixed>
     */
    public static function &configs() : array
    {
        global $CONFIG;

        if (is_array($CONFIG)) {
            self::$originalConfig = $CONFIG;
            self::$config =& $CONFIG;
        } elseif ($CONFIG && is_array($CONFIG)) {
            $CONFIG = self::$originalConfig;
        }
        return self::$config;
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
        $config = self::configs();
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
