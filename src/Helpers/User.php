<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Models\AddonSetting;
use WHMCS\Admin as WHMCSAdmin;
use WHMCS\Session as WHMCSSession;
use WHMCS\User\Admin;
use WHMCS\User\User as WhmcsUserUser;
use function explode;
use function in_array;
use function is_numeric;
use function is_string;

final class User
{
    /**
     * @var array{int, Admin|false}[] $admins The admins
     */
    protected static array $admins = [];

    /**
     * @var array{int, WhmcsUserUser|false}[] $users the users
     */
    protected static array $users = [];

    /**
     * @var array $moduleAccess the module access
     */
    protected static array $moduleAccess = [];

    /**
     * Get the user id
     *
     * @return int|null
     */
    public static function userId() : ?int
    {
        $userId = WHMCSSession::get('uid');
        return is_numeric($userId) ? (int) $userId : null;
    }

    /**
     * Get the admin id
     *
     * @return int|null
     */
    public static function adminId() : ?int
    {
        $adminId = WHMCSAdmin::getID();
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
     * @return WhmcsUserUser|null
     */
    public static function user() : ?WhmcsUserUser
    {
        $userId = self::userId();
        if ($userId === null) {
            return null;
        }
        if (isset(self::$users[$userId])) {
            return self::$users[$userId]?:null;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        self::$users[$userId] = WhmcsUserUser::find($userId)?:false;
        return self::$users[$userId]?:null;
    }

    /**
     * Check if the admin allowed to access the module
     *
     * @param string $moduleName
     * @return bool true if the admin is allowed to access the module
     */
    public static function adminAllowAccessModule(string $moduleName) : bool
    {
        $admin = User::admin();
        if (!$admin) {
            return false;
        }
        if (isset(self::$moduleAccess[$moduleName])) {
            return self::$moduleAccess[$moduleName];
        }
        $roleId = $admin->getAttribute('roleid');
        self::$moduleAccess[$moduleName] = false;
        if ($roleId === null) {
            return false;
        }
        $settings = AddonSetting::find($moduleName, 'access');
        if (!$settings) {
            return false;
        }
        $values = $settings->getAttribute('value');
        if (!is_string($values)) {
            return false;
        }
        $allowedRoles = explode(',', $values);
        return self::$moduleAccess[$moduleName] = in_array($roleId, $allowedRoles);
    }
}
