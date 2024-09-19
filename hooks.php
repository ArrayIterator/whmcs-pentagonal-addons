<?php
declare(strict_types=1);

/**
 * Pentagonal Addons
 *
 * @package     Pentagonal Addons
 * @link        https://www.pentagonal.org/
 * @license     MIT
 * @see         Core::VERSION for version
 */
namespace Pentagonal\Neon\WHMCS\Addon;

use function file_exists;

if (!defined("WHMCS")) {
    header("Location: ../../index.php");
    exit;
}

/**
 * @return ?Core
 */
return (function () {
    if (!function_exists('add_hook')) {
        return null;
    }
    // check if php version less than 7.4
    if (PHP_VERSION_ID < 70400) {
        return null;
    }

    static $classExists = null;
    if ($classExists !== null) {
        return $classExists ? Core::factory() : null;
    }
    $classExists = false;
    if (!file_exists(__DIR__ .'/vendor/autoload.php')) {
        return null;
    }
    // require autoload
    require __DIR__ .'/vendor/autoload.php';
    $classExists = class_exists(Core::class);
    if (!$classExists) {
        spl_autoload_register(static function ($className) {
            $namespace = __NAMESPACE__ . '\\';
            $directory = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
            if (strpos($className, $namespace) !== 0) {
                return;
            }
            $className = substr($className, strlen($namespace));
            $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
            $file = $directory . $className . '.php';
            if (file_exists($file)) {
                require $file;
            }
        });
    }

    $classExists = class_exists(Core::class);
    if (!$classExists) {
        return null;
    }
    return Core::factory()->dispatch();
})();
