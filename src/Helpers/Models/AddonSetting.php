<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers\Models;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Throwable;
use WHMCS\Module\Addon\Setting;

final class AddonSetting
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
        $performance = Performance::profile('find', self::class, [
            'module' => $moduleName,
            'setting' => $setting,
        ]);
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            return Setting::where([
                'module' => $moduleName,
                'setting' => $setting
            ])->first() ?: null;
        } finally {
            $performance->stop();
        }
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
        $performance = Performance::profile('save', self::class, [
            'module' => $moduleName,
            'setting' => $setting,
            'value' => $value,
        ]);
        try {
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
        } finally {
            $performance->stop();
        }
    }
}
