<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use Pentagonal\Neon\WHMCS\Addon\Core;
use WHMCS\Utility\Environment\WebHelper;
use function ltrim;
use function rtrim;
use function trim;
use function urlencode;

final class URL
{
    public const ADDON_FILE = 'addonmodules.php';

    /**
     * @var string|null $addonBase the module base
     */
    private static $addonBase = null;

    /**
     * @var string|null $baseURL the base url
     */
    private static $baseURL = null;

    /**
     * @var string|null $adminURL the admin url
     */
    private static $adminURL = null;

    /**
     * Get admin URL
     *
     * @param string $path
     * @return string
     */
    public static function adminUrl(string $path = ''): string
    {
        if (self::$adminURL === null) {
            self::$adminURL = rtrim(WebHelper::getAdminBaseUrl(), '/');
        }
        return self::$adminURL . '/' . ltrim($path, '/');
    }

    /**
     * Get module URL
     *
     * @param string $path
     * @return string
     */
    public static function moduleURL(string $path = ''): string
    {
        return self::baseUrl('/modules/') . ltrim($path, '/');
    }

    /**
     * Get base URL
     *
     * @param string $path
     * @return string
     */
    public static function baseUrl(string $path = ''): string
    {
        if (self::$baseURL === null) {
            self::$baseURL = rtrim(WebHelper::getBaseUrl(), '/');
        }
        return self::$baseURL . '/' . ltrim($path, '/');
    }

    /**
     * Get addon URL
     *
     * @param string $path
     * @return string
     */
    public static function addOnsURL(string $path = ''): string
    {
        return self::baseUrl('/modules/addons/') . ltrim($path, '/');
    }

    /**
     * Get template URL
     *
     * @param string $path
     * @return string
     */
    public static function templatesUrl(string $path = ''): string
    {
        return self::baseUrl('/templates/') . ltrim($path, '/');
    }

    /**
     * Get asset URL
     *
     * @param string $path
     * @return string
     */
    public static function assetUrl(string $path = ''): string
    {
        return self::baseUrl('/assets/') . ltrim($path, '/');
    }

    /**
     * Get theme URL
     *
     * @param string $path
     * @return string
     */
    public static function themeUrl(string $path = ''): string
    {
        $name = Core::factory()->getTheme()->getName();
        return self::baseUrl('/templates/' . $name . '/') . ltrim($path, '/');
    }

    /**
     * Get pentagonal addon URL
     *
     * @param string $path
     * @return string
     */
    public static function addonUrl(string $path = ''): string
    {
        if (!self::$addonBase) {
            self::$addonBase = Core::factory()->getAddon()->getAddonName();
        }

        return self::baseUrl('/modules/addons/' . self::$addonBase . '/') . ltrim($path, '/');
    }

    /**
     * Get admin addon URL
     *
     * @param string $page
     * @return string
     */
    public static function addonPagUrl(string $page = ''): string
    {
        $page = trim($page);
        if ($page) {
            $page = '&page=' . urlencode($page);
        }
        return self::adminUrl(self::ADDON_FILE . '?module=' . Core::factory()->getAddon()->getAddonName() . $page);
    }
}
