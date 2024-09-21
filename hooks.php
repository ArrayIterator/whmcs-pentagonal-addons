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

if (!defined("WHMCS")) {
    header("Location: ../../index.php");
    exit;
}

/**
 * @return ?Core
 */
return (function () {
    require_once __DIR__ . '/src/Singleton.php';
    return Singleton::dispatch();
})();
