<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use function array_key_exists;
use function is_array;
use function session_start;
use function session_status;
use const PHP_SESSION_ACTIVE;

/**
 * Class SessionFlash - Flash Session
 */
final class SessionFlash
{
    /**
     * @var string SESSION_KEY_IDENTIFIER the session key identifier
     */
    public const SESSION_KEY_IDENTIFIER = 'pentagonal_flash_session';

    private $all;
    /**
     * @var array $current the current session
     */
    private $current = [];

    /**
     * @var array $next the next session
     */
    private $next = [];

    /**
     * @var bool $init the session initialized
     */
    private static $instance = null;

    /**
     * @var bool $initialized the session initialized
     */
    private $initialized = false;

    private function __construct()
    {
        $this->init();
    }

    /**
     * Get Instance
     *
     * @return self
     */
    public static function getInstance() : self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Init
     *
     * @return void
     */
    private function init() : void
    {
        if ($this->initialized) {
            return;
        }
        if (!is_array($_SESSION)) {
            return;
        }
        $this->initialized = true;
        if (is_array($_SESSION[self::SESSION_KEY_IDENTIFIER]??null)) {
            $this->current = $_SESSION[self::SESSION_KEY_IDENTIFIER];
        }
        $this->all = $_SESSION;
        unset($_SESSION[self::SESSION_KEY_IDENTIFIER]);
    }

    /**
     * Flash session - set session to next request
     *
     * @param string $key
     * @param $value
     * @return void
     */
    public static function flash(string $key, $value) : void
    {
        $instance = self::getInstance();
        $instance->next[$key] = $value;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[self::SESSION_KEY_IDENTIFIER] = $instance->next;
    }

    /**
     * Get session
     *
     * @param string $key
     * @param $default
     * @return mixed|null
     */
    public static function get(string $key, $default = null)
    {
        $instance = self::getInstance();
        return array_key_exists($key, $instance->current) ? $instance->current[$key] : $default;
    }

    /**
     * @param string $key
     * @param $default
     * @return mixed|null
     */
    public static function getNext(string $key, $default = null)
    {
        $instance = self::getInstance();
        return array_key_exists($key, $instance->next) ? $instance->next[$key] : $default;
    }

    /**
     * Cancel current flash
     *
     * @param string $key
     * @return void
     */
    public static function cancel(string $key) : void
    {
        $instance = self::getInstance();
        unset($instance->next[$key]);
        $_SESSION[self::SESSION_KEY_IDENTIFIER] = $instance->next;
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function has(string $key) : bool
    {
        $instance = self::getInstance();
        return array_key_exists($key, $instance->current);
    }

    /**
     * @return array
     */
    public static function current() : array
    {
        return self::getInstance()->current;
    }

    /**
     * @return array
     */
    public static function next() : array
    {
        return self::getInstance()->next;
    }
}
