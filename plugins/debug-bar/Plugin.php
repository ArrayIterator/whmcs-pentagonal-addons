<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Plugins\DebugBar;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractPlugin;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Libraries\SmartyAdmin;
use function is_string;
use function json_encode;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

class Plugin extends AbstractPlugin
{
    /**
     * @inheritDoc
     */
    protected function doLoad()
    {
        $this
            ->getPlugins()
            ->getCore()
            ->getEventManager()
            ->attach(SmartyAdmin::EVENT_SMARTY_ADMIN_FOOTER, [$this, 'renderBenchmark'], true);
    }

    /**
     * @param mixed $vars
     * @return mixed
     */
    public function renderBenchmark($vars)
    {
        if (!Performance::getInstance()->isEnabled()
            || !$this
                ->getPlugins()
                ->getCore()
                ->getEventManager()
                ->in(SmartyAdmin::EVENT_SMARTY_ADMIN_FOOTER)
        ) {
            return $vars;
        }

        $smarty = $this->getPlugins()->getCore()->getSmartyAdmin();
        $output = $smarty->getTemplateVars('post_load_output');
        $output = !is_string($output) ? '' : $output;
        $output .= sprintf(
            '<script type="application/json" id="pentagonal-performance-debug-bar-profiler">%s</script>',
            json_encode(Performance::getInstance(), JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)
        );
        $smarty->assign('post_load_output', $output);
        return $vars;
    }
}
