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
use Pentagonal\Neon\WHMCS\Addon\Core;
use WHMCS\Database\Capsule;

// phpcs:disable PSR1.Files.SideEffects
if (!defined("WHMCS")) {
    header("Location: ../../index.php");
    exit;
}

/**
 * Immutable
 * @noinspection PhpExpressionResultUnusedInspection
 */
(function () {
    /**
     * WHMCS Addon Config
     *
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpUnused
     * @return array{
     *          name: string,
     *          description: string,
     *          version: string,
     *          author: string,
     *          language: string,
     *          fields?: array<string, array{
     *              FriendlyName: string,
     *              Type: string,
     *              Description: string,
     *              Default: string
     *          }>
     * }
     */
    function pentagonal_config()
    {
        if ((PHP_VERSION_ID < 70200)) {
            return [
                'name' => 'Pentagonal Addon',
                'description' => 'PHP Version must be 7.2 or greater',
                'version' => 'unknown',
                'author' => 'Pentagonal',
                'language' => 'english',
            ];
        }

        $core = include __DIR__ . '/hooks.php';
        if (!$core instanceof Core) {
            return [
                'name' => 'Pentagonal Addon',
                'description' => '-- Invalid Module --',
                'version' => 'unknown',
                'author' => 'Pentagonal',
                'language' => 'english',
            ];
        }

        /** @noinspection PhpInternalEntityUsedInspection */
        return $core->getAddon()->config();
    }

    /**
     * WHMCS Addon Activation - Run on Activation
     *
     * @return array{
     *     status: string,
     *     description: string
     * }
     * @noinspection PhpUnused
     * @noinspection PhpMissingReturnTypeInspection
     */
    function pentagonal_activate()
    {
        // check if php version less than 7.2
        if (PHP_VERSION_ID < 70200) {
            return [
                'status' => 'error',
                'description' => 'PHP Version must be 7.2 or greater'
            ];
        }
        $core = include __DIR__ . '/hooks.php';
        if (!$core instanceof Core) {
            return [
                'status' => 'error',
                'description' => 'Invalid Module'
            ];
        }

        $module = $_REQUEST['module'] ?? null;
        $moduleName = basename(__DIR__);
        if ($moduleName !== $module || !class_exists(Capsule::class)) {
            return [
                'status' => 'error',
                'description' => 'Invalid Module Action'
            ];
        }

        /** @noinspection PhpInternalEntityUsedInspection */
        return $core->getAddon()->activate();
    }

    /**
     * The pentagonal deactivation
     *
     * @return array{
     *     status: string,
     *     description: string
     * }
     * @noinspection PhpMissingReturnTypeInspection
     */
    function pentagonal_deactivate()
    {
        // check if php version less than 7.2
        if (PHP_VERSION_ID < 70200) {
            return [
                'status' => 'success',
                'description' => 'PHP Version must be 7.2 or greater'
            ];
        }
        $core = include __DIR__ . '/hooks.php';
        if (!$core instanceof Core) {
            return [
                'status' => 'error',
                'description' => 'Invalid Module'
            ];
        }
        /** @noinspection PhpInternalEntityUsedInspection */
        return Core::factory()->getAddon()->deactivate();
    }

    /**
     * The pentagonal admin output
     *
     * @param $vars
     * @return void
     * @noinspection PhpUnused
     */
    function pentagonal_output($vars)
    {
        $core = include __DIR__ . '/hooks.php';
        if (!$core instanceof Core) {
            return;
        }
        /** @noinspection PhpInternalEntityUsedInspection */
        $core->getAddon()->output($vars);
    }

    /**
     * The pentagonal upgrade
     *
     * @param $vars
     * @return void
     */
    function pentagonal_upgrade($vars)
    {
        $core = include __DIR__ . '/hooks.php';
        if (!$core instanceof Core) {
            return;
        }
        /** @noinspection PhpInternalEntityUsedInspection */
        Core::factory()->getAddon()->upgrade($vars);
    }
})();
