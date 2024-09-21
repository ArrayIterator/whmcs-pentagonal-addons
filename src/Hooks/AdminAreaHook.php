<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Hooks;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractHook;
use Pentagonal\Neon\WHMCS\Addon\Addon;
use function is_array;
use function json_encode;
use const JSON_UNESCAPED_SLASHES;

/**
 * Version Hook
 * Display the version of the addon
 */
class AdminAreaHook extends AbstractHook
{
    /**
     * @var string $hooks the hook name
     */
    protected $hooks = 'AdminAreaPage';

    /**
     * @inheritDoc
     */
    protected function dispatch($vars)
    {
        if (!$this->getHooksService()->getCore()->getAddon()->isAllowedAccessAddonPage()) {
            return $vars;
        }
        $vars = !is_array($vars) ? [] : $vars;
        $url = $this->getHooksService()->getCore()->getUrl();
        $link = json_encode($url->getAddonPageUrl(), JSON_UNESCAPED_SLASHES);
        $name = json_encode(Addon::ADDON_CONFIG['name'], JSON_UNESCAPED_SLASHES);
        $definitions = [
            'addon_name' => $this->getHooksService()->getCore()->getAddon()->getAddonName(),
            'addon_url' => $url->getAddonUrl(),
            'addons_url' => $url->getAddOnsURL(),
            'admin_url' => $url->getAdminUrl(),
            'base_url' => $url->getBaseUrl(),
            'theme_url' => $url->getThemeUrl(),
            'templates_url' => $url->getTemplatesUrl(),
            'asset_url' => $url->getAssetUrl(),
            'module_url' => $url->getModulesURL(),
        ];
        $definitions  = json_encode($definitions, JSON_UNESCAPED_SLASHES);
        $vars['jscode'] ??= '';
        // inject button to menu
        $vars['jscode'] .= <<<JS

;((w) => {
    const d = w.document;
    const run = () => { 
       const menuAutomation = d.getElementById('Menu-Automation-Status')?.closest('li');
       if (!menuAutomation) {
           return;
       }
       const li = d.createElement('li');
       let _class = menuAutomation.className !== '' ? menuAutomation.className : 'bt';
       li.classList.add('pentagonal-menu-button', _class);
       menuAutomation.before(li);
       li.innerHTML = `<a href={$link}
            class="menu-link-icon"
            data-toggle="tooltip"
            data-placement="bottom"
            title={$name}>
             <i class="fa fa-terminal always"></i>
             <span class="visible-sidebar">Pentagonal</span>
       </a>`;
   }
    ['complete', 'interactive'].includes(d.readyState) ? run() : w.addEventListener('DOMContentLoaded', run);
})(window);

((w) => {
    w['pentagonal_definition_uri'] || Object.defineProperty(w, 'pentagonal_definition_uri', {
        writable: false,
        configurable: false,
        value : {$definitions}
    })
})(window);
JS;
        return $vars;
    }
}
