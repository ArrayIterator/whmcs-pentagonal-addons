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

    /**
     * @var array $current the current session
     */
    private array $current = [];

    /**
     * @var array $next the next session
     */
    private array $next = [];

    /**
     * @var ?SessionFlash $init the instance
     */
    private static ?SessionFlash $instance = null;

    /**
     * @var bool $initialized the session initialized
     */
    private bool $initialized = false;

    /**
     * SessionFlash constructor.
     */
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
        return self::$instance ??= new self();
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
     * Get next session
     *
     * @param string $key
     * @param $default
     * @return mixed|null
     * @noinspection PhpUnused
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
