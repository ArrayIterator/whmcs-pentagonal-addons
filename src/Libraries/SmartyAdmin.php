<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries;

use Exception;
use Pentagonal\Neon\WHMCS\Addon\Core;
use Pentagonal\Neon\WHMCS\Addon\Helpers\ApplicationConfig;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Smarty;
use SmartyBC;
use Throwable;
use WHMCS\Application\Support\Facades\Di;
use function function_exists;
use function is_string;
use const SMARTY_MBSTRING;

class SmartyAdmin extends SmartyBC
{

    /**
     * @var Core $core the core
     */
    private Core $core;

    /**
     * @param string $templateDir Template Directory
     */
    public function __construct(Core $core, string $templateDir)
    {
        $this->core = $core;
        self::$_MBSTRING = SMARTY_MBSTRING && function_exists("mb_split");
        parent::__construct();

        $this->setCaching(Smarty::CACHING_OFF);
        $this->setTemplateDir($templateDir);
        $directory = ApplicationConfig::get("templates_compiledir");
        if (is_string($directory)) {
            $this->setCompileDir($directory);
        }
        $this->assignDefault();
    }

    /**
     * @return Core
     */
    public function getCore(): Core
    {
        return $this->core;
    }

    /**
     * Assign Default Variables
     *
     * @return void
     */
    protected function assignDefault()
    {
        $url = $this->getCore()->getUrl();
        $this->assign([
            'addon_url' => $url->getAddonUrl(),
            'addons_url' => $url->getAddOnsURL(),
            'admin_url' => $url->getAdminUrl(),
            'base_url' => $url->getBaseUrl(),
            'theme_url' => $url->getThemeUrl(),
            'templates_url' => $url->getTemplatesUrl(),
            'asset_url' => $url->getAssetUrl(),
            'modules_url' => $url->getModulesURL(),
        ]);
        $functions = [
            'addon_url' => [$url, 'getAddonUrl'],
            'addons_url' => [$url, 'getAddOnsURL'],
            'admin_url' => [$url, 'getAdminUrl'],
            'base_url' => [$url, 'getBaseUrl'],
            'theme_url' => [$url, 'getThemeUrl'],
            'templates_url' => [$url, 'getTemplatesUrl'],
            'asset_url' => [$url, 'getAssetUrl'],
            'modules_url' => [$url, 'getModulesURL'],
        ];
        foreach ($functions as $name => $callback) {
            try {
                $this->registerPlugin(Smarty::PLUGIN_FUNCTION, $name, function ($args) use ($callback) {
                    $path = $args['path']??null;
                    return $callback((string) $path);
                });
            } catch (Throwable $e) {
                Logger::error(
                    $e,
                    [
                        'type' => 'error',
                        'method' => 'assignDefault',
                        'smarty_function' => $name
                    ]
                );
                // pass
            }
        }
        try {
            $this->registerPlugin("modifier", "sprintf2", ["WHMCS\\Smarty", "sprintf2Modifier"]);
            $this->registerPlugin(Smarty::PLUGIN_FUNCTION, "lang", ["WHMCS\\Smarty", "langFunction"]);
            $this->registerFilter("pre", ["WHMCS\\Smarty", "preFilterSmartyTemplateVariableScopeResolution"]);
            $policy = Di::getFacadeApplication()->make("WHMCS\\Smarty\\Security\\Policy", [$this, 'system']);
            $this->enableSecurity($policy);
        } catch (Exception $e) {
            $this->trigger_error($e->getMessage());
        }
    }
}
