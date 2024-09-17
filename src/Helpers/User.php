<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use WHMCS\Module\Addon\Setting;
use WHMCS\User\Admin;
use WHMCS\User\User as WhmcsUser;
use function is_numeric;

final class User
{
    /**
     * @var array{int, Admin|false}[] $admins The admins
     */
    protected static $admins = [];

    /**
     * @var array{int, WhmcsUser|false}[] $users the users
     */
    protected static $users = [];

    /**
     * @var array $moduleAccess the module access
     */
    protected static $moduleAccess = [];

    /**
     * Get the user id
     *
     * @return int|null
     */
    public static function userId() : ?int
    {
        $userId = $_SESSION['uid'] ?? null;
        return is_numeric($userId) ? (int) $userId : null;
    }

    /**
     * Get the admin id
     *
     * @return int|null
     */
    public static function adminId() : ?int
    {
        $adminId = $_SESSION['adminid'] ?? null;
        return is_numeric($adminId) ? (int) $adminId : null;
    }

    /**
     * Get the admin
     *
     * @return Admin|null
     */
    public static function admin() : ?Admin
    {
        $adminId = self::adminId();
        if ($adminId === null) {
            return null;
        }
        if (isset(self::$admins[$adminId])) {
            return self::$admins[$adminId]?:null;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        self::$admins[$adminId] = Admin::find($adminId)?:false;
        return self::$admins[$adminId]?:null;
    }

    /**
     * Get the user
     *
     * @return WhmcsUser|null
     */
    public static function user() : ?WhmcsUser
    {
        $userId = self::userId();
        if ($userId === null) {
            return null;
        }
        if (isset(self::$users[$userId])) {
            return self::$users[$userId]?:null;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        self::$users[$userId] = WhmcsUser::find($userId)?:false;
        return self::$users[$userId]?:null;
    }

    public static function adminAllowAccessModule(string $moduleName)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $settings = Setting::where('module', trim($moduleName))
            ->where('setting', 'access')
            ->first();
        print_r($settings);
    }
}
