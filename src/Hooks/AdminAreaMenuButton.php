<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Hooks;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractHook;
use Pentagonal\Neon\WHMCS\Addon\Addon;
use Pentagonal\Neon\WHMCS\Addon\Helpers\URL;
use function is_string;
use function json_encode;
use const JSON_UNESCAPED_SLASHES;

/**
 * Version Hook
 * Display the version of the addon
 */
class AdminAreaMenuButton extends AbstractHook
{
    /**
     * @var string $hooks the hook name
     */
    protected $hooks = 'AdminAreaFooterOutput';

    /**
     * @inheritDoc
     */
    protected function dispatch($vars)
    {
        if (!is_string($vars)) {
            $vars = '';
        }
        $link = json_encode(URL::addonPageUrl(), JSON_UNESCAPED_SLASHES);
        $name = json_encode(Addon::ADDON_CONFIG['name'], JSON_UNESCAPED_SLASHES);
        $definitions = [
            'addon_name' => $this->getHooksService()->getCore()->getAddon()->getAddonName(),
            'addon_url' => URL::addonUrl(),
            'addons_url' => URL::addOnsURL(),
            'admin_url' => URL::adminUrl(),
            'base_url' => URL::baseUrl(),
            'theme_url' => URL::themeUrl(),
            'templates_url' => URL::templatesUrl(),
            'asset_url' => URL::assetUrl(),
            'module_url' => URL::moduleURL(),
        ];
        $definitions  = json_encode($definitions, JSON_UNESCAPED_SLASHES);
        // inject button to menu
        return $vars . <<<HTML
<script type="text/javascript">
(() => {
   const menuAutomation = document.getElementById('Menu-Automation-Status')?.closest('li');
   if (!menuAutomation) {
       return;
   }
   const li = document.createElement('li');
   let _class = menuAutomation.className !== '' ? menuAutomation.className : 'bt';
   li.classList.add('pentagonal-menu-button', _class);
   menuAutomation.before(li);
   li.innerHTML = `<a href={$link} class="menu-link-icon" data-toggle="tooltip" data-placement="bottom" title={$name}>
         <i class="fa fa-terminal always"></i>
         <span class="visible-sidebar">Pentagonal</span>
   </a>`;
})();

((w) => {
    w['pentagonal_definition_uri'] || Object.defineProperty(w, 'pentagonal_definition_uri', {
        writable: false,
        configurable: false,
        value : {$definitions}
    })
})(window);
</script>
HTML;
    }
}
