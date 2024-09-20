<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Helpers\Models;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
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
        $performance = Performance::profile('find', self::class, [
            'setting' => $setting,
        ]);
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            return Setting::where([
                'setting' => $setting
            ])->first() ?: null;
        } finally {
            $performance->stop();
        }
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
        $performance = Performance::profile('save', self::class, [
            'setting' => $setting,
            'value' => $value,
        ]);
        try {
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
        } finally {
            $performance->stop();
        }
    }
}
