<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Models\AddonSetting;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Models\Configuration;
use Pentagonal\Neon\WHMCS\Addon\Helpers\SessionFlash;
use Pentagonal\Neon\WHMCS\Addon\Helpers\URL;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Config;
use Pentagonal\Neon\WHMCS\Addon\Helpers\User;
use Throwable;
use WHMCS\Admin\AdminServiceProvider;
use function array_filter;
use function array_unique;
use function basename;
use function debug_backtrace;
use function defined;
use function dirname;
use function explode;
use function header;
use function headers_sent;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_dir;
use function is_file;
use function is_numeric;
use function is_string;
use function preg_match;
use function preg_replace;
use function safe_serialize;
use function safe_unserialize;
use function sprintf;
use function str_replace;
use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const DIRECTORY_SEPARATOR;

final class Addon
{
    /**
     * @var string SESSION_WELCOME_FLASH_NAME the session welcome flash name
     */
    public const SESSION_WELCOME_FLASH_NAME = 'pentagonal_welcome_flash';

    /**
     * @var string VERSION the addon version
     */
    public const VERSION = '1.0.0';

    /**
     * @var string ADMIN_ADDON_CONFIG the admin addon config
     */
    public const EVENT_ADDON_ADMIN_OUTPUT = 'AdminOutput';

    /**
     * @var string ADMIN_ADDON_CONFIG the admin addon config
     */
    public const EVENT_ADDON_ADMIN_CONFIG = 'AdminConfig';

    /**
     * @var string ADMIN_ADDON_UPGRADE the addon upgrade
     */
    public const EVENT_ADDON_UPGRADE = 'AdminUpgrade';

    /**
     * @var array ADDON_CONFIG the addon config
     */
    public const ADDON_CONFIG = [
        'name' => 'Pentagonal Addon',
        'description' => 'Addon to help missing features in Pentagonal WHMCS Products',
        'version' => self::VERSION,
        'author' => 'Pentagonal',
    ];

    /**
     * @var Core $core the core
     */
    protected $core;

    /**
     * @var string $addonName the addon name
     */
    protected $addonName;

    /**
     * @var string $addonFile the addon file
     */
    protected $addonFile;

    /**
     * @var string $addonDirectory the addon directory
     */
    protected $addonDirectory;

    /**
     * @var bool $configCalled the config called
     */
    protected $configCalled = false;

    /**
     * @var bool $activateCalled the activation called
     */
    protected $activateCalled = false;

    /**
     * @var bool $deactivateCalled the deactivation called
     */
    protected $deactivateCalled = false;

    /**
     * @var bool $outputCalled the output called
     */
    protected $outputCalled = false;

    /**
     * @var bool $upgradeCalled the upgrade called
     */
    protected $upgradeCalled = false;

    /**
     * @var bool $allowedAccessAddonPage allow addon page
     */
    protected $allowedAccessAddonPage = null;

    /**
     * @var bool $isAddonFile is addon file
     */
    protected $isAddonFile = null;

    /**
     * Addon constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        $this->core = $core;
    }

    /**
     * Get the addon directory
     *
     * @return string
     */
    public function getAddonDirectory(): string
    {
        if (!isset($this->addonDirectory)) {
            $baseDir = dirname(__DIR__);
            $this->addonDirectory = $baseDir;
        }
        return $this->addonDirectory;
    }

    /**
     * Get the addon name
     *
     * @return string the addon name
     */
    public function getAddonName(): string
    {
        if (!isset($this->addonName)) {
            $baseDir = $this->getAddonDirectory();
            $this->addonName = basename($baseDir);
        }
        return $this->addonName;
    }

    /**
     * @return Core
     */
    public function getCore(): Core
    {
        return $this->core;
    }

    /**
     * @return bool
     */
    public function isActivateCalled(): bool
    {
        return $this->activateCalled;
    }

    /**
     * @return bool
     */
    public function isConfigCalled(): bool
    {
        return $this->configCalled;
    }

    /**
     * @return bool
     */
    public function isDeactivateCalled(): bool
    {
        return $this->deactivateCalled;
    }

    /**
     * @return bool
     */
    public function isOutputCalled(): bool
    {
        return $this->outputCalled;
    }

    /**
     * @return bool
     */
    public function isUpgradeCalled(): bool
    {
        return $this->upgradeCalled;
    }

    /**
     * Get the addon file
     *
     * @return string the addon file
     */
    public function getAddonFile(): string
    {
        if (!isset($this->addonFile)) {
            $baseDir = $this->getAddonDirectory();
            $addonName = $this->getAddonName();
            $hookFile = $baseDir . DIRECTORY_SEPARATOR . $addonName . '.php';
            $this->addonFile = $hookFile;
        }
        return $this->addonFile;
    }

    /**
     * @note only load in pentagonal.php
     * @see pentagonal_config()
     * @internal
     * @return array
     */
    public function config() : array
    {
        if (!$this->getCore()->isAdminAreaRequest()) {
            return [
                'name' => 'Pentagonal Addon',
                'description' => '-- Invalid Module --',
                'version' => self::VERSION,
                'author' => 'Pentagonal',
                'language' => 'english',
            ];
        }
        // only load in pentagonal.php
        $hookFile = $this->getAddonFile();
        $configFunction = $this->getAddonName() . '_config';
        $debug = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
        if (($debug[0]['file']??null) !== $hookFile || ($debug[1]['function']??null) !== $configFunction) {
            return [
                'name' => 'Pentagonal Addon',
                'description' => '-- Invalid Module Called --',
                'version' => self::VERSION,
                'author' => 'Pentagonal',
                'language' => 'english',
            ];
        }
        $this->configCalled = true;
        $this->getCore()->getEventManager()->apply(
            self::EVENT_ADDON_ADMIN_CONFIG,
            $this
        );
        return self::ADDON_CONFIG;
    }

    /**
     * Check if the current user is allowed to access the admin addon page
     *
     * @return bool
     */
    public function isAllowedAccessAddonPage() : bool
    {
        if (is_bool($this->allowedAccessAddonPage)) {
            return $this->allowedAccessAddonPage;
        }
        $this->allowedAccessAddonPage = false;
        $admin = User::admin();
        if (!$admin) {
            return false;
        }
        $roleId = $admin->getAttribute('roleid');
        if ($roleId === null) {
            return false;
        }
        $moduleName = $this->getAddonName();
        $settings = AddonSetting::find($moduleName, 'access');
        if (!$settings) {
            return false;
        }
        $values = $settings->getAttribute('value');
        if (!is_string($values)) {
            return false;
        }
        $allowedRoles = explode(',', $values);
        $this->allowedAccessAddonPage = in_array($roleId, $allowedRoles);
        return $this->allowedAccessAddonPage;
    }

    /**
     * Check if request is in addon file
     *
     * @return bool
     */
    public function isAdminAddonFileRequest() : bool
    {
        if (is_bool($this->isAddonFile)) {
            return $this->isAddonFile;
        }
        $this->isAddonFile = false;
        if (!$this->getCore()->isAdminAreaRequest()) {
            return false;
        }
        $self = str_replace('\\', '//', $this->getCore()->getApplication()->getPhpSelf());
        $base = AdminServiceProvider::getAdminRouteBase() . '/' . URL::ADDON_FILE;
        $self = ltrim(preg_replace('#/+#', '/', $self), '/');
        $base = ltrim(preg_replace('#/+#', '/', $base), '/');
        $this->isAddonFile = $self === $base;
        return $this->isAddonFile;
    }

    /**
     * Check if it was on addon page
     *
     * @return bool
     */
    public function isAddonPage() : bool
    {
        if (!$this->isAdminAddonFileRequest() || !$this->isAllowedAccessAddonPage()) {
            return false;
        }
        $module = $_GET['module']??null;
        return $module === $this->getAddonName();
    }

    /**
     * @note only load in pentagonal.php
     * @see pentagonal_activate()
     * @internal
     * @return array{
     *     status: string,
     *     description: string
     * }
     */
    public function activate() : array
    {
        $module = $_REQUEST['module']??null;
        $error = [
            'status'  => 'error',
            'description' => sprintf('Module %s Is Invalid', $this->getAddonName())
        ];
        if (!$this->getCore()->isAdminAreaRequest()) {
            return $error;
        }
        // only load in pentagonal.php
        $hookFile = $this->getAddonFile();
        $configFunction = $this->getAddonName() . '_activate';
        $debug = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
        if (($debug[0]['file']??null) !== $hookFile
            || ($debug[1]['function']??null) !== $configFunction
            || !$this->isConfigCalled()
        ) {
            return [
                'status'  => 'error',
                'description' => 'Invalid Module Activation Called'
            ];
        }
        if ($this->isActivateCalled()) {
            return [
                'status'  => 'success',
                'description' => sprintf('Module %s Activated', $this->getAddonName())
            ];
        }

        $this->activateCalled = true;

        $success = [
            'status'  => 'success',
            'description' => sprintf('Module %s Activated', $this->getAddonName())
        ];

        Logger::debug('Activating Module');
        $admin = User::admin();
        $roleId = $admin ? $admin->getAttribute('roleid') : null;
        if (!is_numeric($roleId)) {
            return $error;
        }
        try {
            $settings = AddonSetting::find($module, 'access');
            $succeed = true;
            if ($settings === null) {
                $succeed = AddonSetting::save($module, 'access', $roleId);
            } else {
                $data = $settings->getAttribute('value');
                if (!is_string($data)) {
                    $data = '';
                }
                $data = explode(',', $data);
                if (!in_array($roleId, $data)) {
                    $settings->setAttribute('value', $roleId);
                    $succeed = $settings->save();
                }
            }
            if (!$succeed) {
                return $success;
            }
            if (headers_sent()) {
                return $error;
            }
            if (!defined('ROOTDIR')
                || !is_string(ROOTDIR)
                || !is_dir(ROOTDIR)
            ) {
                return $error;
            }
            $application = $this->getCore()->getApplication();
            $phpSelf = $application->getPhpSelf();
            if (!is_string($phpSelf) || !preg_match('#[/\\\]configaddonmods\.php$#', $phpSelf)) {
                return $error;
            }
            $activeModules = Config::get("ActiveAddonModules");
            if (!is_string($activeModules)) {
                return $error;
            }
            $adminPath = ROOTDIR .  DIRECTORY_SEPARATOR . dirname($application->getPhpSelf());
            $file = $adminPath . '/addonmodules.php';
            if (!is_file($file)) {
                return $success;
            }
            $activeModules = array_filter(explode(",", $activeModules));
            $activeModules[] = $module;
            sort($activeModules);
            $activeModules = array_unique($activeModules);
            if (!Configuration::save('ActiveAddonModules', implode(",", $activeModules))) {
                return $success;
            }
            // save versions
            $version = AddonSetting::find($module, 'version');
            if ($version) {
                $version->setAttribute('value', self::VERSION);
                $version->save();
            } else {
                AddonSetting::save($module, 'version', self::VERSION);
            }

            // save permissions
            $addonPermissions = [];
            $addonPerms = Configuration::find('AddonModulesPerms');
            if ($addonPerms) {
                $addonPermissions = safe_unserialize($addonPerms->getAttribute('value'));
            }
            $addonPermissions[$roleId] = $module;
            $addonPermissions[$roleId][$module] = self::ADDON_CONFIG['name'];
            if (!Configuration::save('AddonModulesPerms', safe_serialize($addonPermissions))) {
                return $success;
            }

            // append hooks
            $addonModuleHooks = Configuration::find('AddonModulesHooks');
            $addonHooks = [];
            if ($addonModuleHooks) {
                $addonHooks = explode(',', (string) $addonModuleHooks->getAttribute('value'));
            }
            array_unshift($addonHooks, $module);
            if (!Configuration::save('AddonModulesHooks', implode(',', $addonHooks))) {
                return $success;
            }

            SessionFlash::flash(self::SESSION_WELCOME_FLASH_NAME, true);
            $url = URL::adminUrl('addonmodules.php?module=' . $module . '&ref=welcome');
            header('Location: ' . $url, true, 302);
            exit(0);
        } catch (Throwable $e) {
            Logger::error(
                $e,
                [
                    'type' => 'addon',
                    'method' => 'activate',
                    'module' => $module
                ]
            );
            return [
                'status'  => 'error',
                'description' => $e->getMessage()
            ];
        }
    }

    /**
     * @note only load in pentagonal.php
     * @see pentagonal_deactivate()
     * @internal
     * @return array{
     *     status: string,
     *     description: string
     * }
     */
    public function deactivate() : array
    {
        if (!$this->getCore()->isAdminAreaRequest()) {
            return [
                'status'  => 'error',
                'description' => 'Invalid Module Deactivation Called'
            ];
        }
        // only load in pentagonal.php
        $hookFile = $this->getAddonFile();
        $configFunction = $this->getAddonName() . '_deactivate';
        $debug = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
        if (($debug[0]['file']??null) !== $hookFile
            || ($debug[1]['function']??null) !== $configFunction
            || !$this->isConfigCalled()
        ) {
            return [
                'status'  => 'error',
                'description' => 'Invalid Module Deactivation Called'
            ];
        }
        if ($this->isDeactivateCalled()) {
            return [
                'status'  => 'success',
                'description' => sprintf('Module %s Deactivated', $this->getAddonName())
            ];
        }

        $this->deactivateCalled = true;
        return [
            'status'  => 'success',
            'description' => sprintf('Module %s Deactivated', $this->getAddonName())
        ];
    }

    /**
     * @note only load in pentagonal.php
     * @see pentagonal_output()
     * @internal
     * @param $vars
     * @return void
     */
    public function output($vars = null)
    {
        if (!$this->getCore()->isAdminAreaRequest()) {
            return;
        }
        if (!is_array($vars) || !$this->isConfigCalled()) {
            return;
        }
        if ($this->isOutputCalled()) {
            return;
        }
        $hookFile = $this->getAddonFile();
        $configFunction = $this->getAddonName() . '_output';
        $debug = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));

        if (($debug[0]['file']??null) !== $hookFile || ($debug[1]['function']??null) !== $configFunction) {
            return;
        }
        $this->outputCalled = true;
        $this->getCore()->getEventManager()->apply(
            self::EVENT_ADDON_ADMIN_OUTPUT,
            $vars
        );
    }

    /**
     * @note only load in pentagonal.php
     * @see pentagonal_upgrade()
     * @internal
     * @param $vars
     * @return void
     */
    public function upgrade($vars = null)
    {
        if (!$this->getCore()->isAdminAreaRequest()) {
            return;
        }
        if (!is_array($vars) || !$this->isConfigCalled()) {
            return;
        }
        if ($this->isUpgradeCalled()) {
            return;
        }
        $hookFile = $this->getAddonFile();
        $configFunction = $this->getAddonName() . '_upgrade';
        $debug = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
        if (($debug[0]['file']??null) !== $hookFile || ($debug[1]['function']??null) !== $configFunction) {
            return;
        }
        $this->upgradeCalled = true;
        $this->getCore()->getEventManager()->apply(
            self::EVENT_ADDON_UPGRADE,
            $vars
        );
    }
}
