<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers\Models;

use Throwable;
use WHMCS\Module\Addon\Setting;

class AddonSetting
{
    /**
     * Get Setting
     *
     * @param string $moduleName
     * @param string $setting
     * @return Setting|null
     */
    public static function find(string $moduleName, string $setting) : ?Setting
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Setting::where([
            'module' => $moduleName,
            'setting' => $setting
        ])->first()?:null;
    }

    /**
     * Save Setting
     *
     * @param string $moduleName
     * @param string $setting
     * @param $value
     * @return bool
     */
    public static function save(string $moduleName, string $setting, $value) : bool
    {
        $object = self::find($moduleName, $setting);
        if ($object instanceof Setting) {
            try {
                $object->setAttribute('value', $value);
                return $object->save();
            } catch (Throwable $e) {
                return false;
            }
        } else {
            try {
                $object = new Setting([
                    'module' => $moduleName,
                    'setting' => $setting,
                ]);
                $object->setAttribute('value', $value);
                return $object->save();
            } catch (Throwable $e) {
                return false;
            }
        }
    }
}
