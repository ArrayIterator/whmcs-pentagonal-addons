<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries;

use Exception;
use Pentagonal\Neon\WHMCS\Addon\Helpers\ApplicationConfig;
use Pentagonal\Neon\WHMCS\Addon\Helpers\URL;
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
     * @param string $templateDir Template Directory
     */
    public function __construct(string $templateDir)
    {
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
     * Assign Default Variables
     *
     * @return void
     */
    protected function assignDefault()
    {
        $this->assign([
            'pentagonal_addon_url' => URL::addonUrl(),
            'addon_url' => URL::addOnsURL(),
            'admin_url' => URL::adminUrl(),
            'base_url' => URL::baseUrl(),
            'theme_url' => URL::themeUrl(),
            'templates_url' => URL::templatesUrl(),
            'asset_url' => URL::assetUrl(),
            'module_url' => URL::moduleURL(),
        ]);
        $functions = [
            'pentagonal_addon_url' => [URL::class, 'addonUrl'],
            'addon_url' => [URL::class, 'addOnsURL'],
            'admin_url' => [URL::class, 'adminUrl'],
            'base_url' => [URL::class, 'baseUrl'],
            'theme_url' => [URL::class, 'themeUrl'],
            'templates_url' => [URL::class, 'templatesUrl'],
            'asset_url' => [URL::class, 'assetUrl'],
            'module_url' => [URL::class, 'moduleURL'],
        ];
        foreach ($functions as $name => $callback) {
            try {
                $this->registerPlugin("function", $name, function ($args) use ($callback) {
                    $path = $args['path']??null;
                    return $callback((string) $path);
                });
            } catch (Throwable $e) {
                // pass
            }
        }
        try {
            $this->registerPlugin("modifier", "sprintf2", ["WHMCS\\Smarty", "sprintf2Modifier"]);
            $this->registerPlugin("function", "lang", ["WHMCS\\Smarty", "langFunction"]);
            $this->registerFilter("pre", ["WHMCS\\Smarty", "preFilterSmartyTemplateVariableScopeResolution"]);
            $policy = Di::getFacadeApplication()->make("WHMCS\\Smarty\\Security\\Policy", [$this, 'system']);
            $this->enableSecurity($policy);
        } catch (Exception $e) {
            $this->trigger_error($e->getMessage());
        }
    }
}
