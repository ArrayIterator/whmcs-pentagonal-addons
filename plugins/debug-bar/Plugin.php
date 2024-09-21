<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Plugins\DebugBar;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractPlugin;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\AdminDispatcher;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use function json_encode;
use function printf;
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
            ->attach(AdminDispatcher::EVENT_ADMIN_OUTPUT_AFTER_RENDER, [$this, 'eventAfterRender'], true);
    }

    /**
     * @param mixed $vars
     * @return mixed
     */
    public function eventAfterRender($vars)
    {
        if (!Performance::getInstance()->isEnabled()
            || !$this
                ->getPlugins()
                ->getCore()
                ->getEventManager()
                ->in(AdminDispatcher::EVENT_ADMIN_OUTPUT_AFTER_RENDER)
        ) {
            return $vars;
        }
        printf(
            '<script type="application/json" id="pentagonal-performance-debug-bar-profiler">%s</script>',
            json_encode(Performance::getInstance(), JSON_UNESCAPED_SLASHES)
        );
        return $vars;
    }
}
