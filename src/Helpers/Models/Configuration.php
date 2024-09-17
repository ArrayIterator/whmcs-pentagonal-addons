<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers\Models;

use Throwable;
use WHMCS\Config\Setting;

class Configuration
{
    /**
     * Get Setting
     *
     * @param string $setting
     * @return Setting|null
     */
    public static function find(string $setting) : ?Setting
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Setting::where([
            'setting' => $setting
        ])->first()?:null;
    }

    /**
     * Save Setting
     *
     * @param string $setting
     * @param $value
     * @return bool
     */
    public static function save(string $setting, $value) : bool
    {
        $object = self::find($setting);
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
