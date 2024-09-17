<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Hooks;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractHook;
use Pentagonal\Neon\WHMCS\Addon\Addon;
use Pentagonal\Neon\WHMCS\Addon\Helpers\HtmlAttributes;
use Pentagonal\Neon\WHMCS\Addon\Helpers\URL;
use function implode;
use function is_string;
use const PHP_INT_MIN;

class AdminAreaHeadOutput extends AbstractHook
{
    protected $priority = PHP_INT_MIN;

    /**
     * @var string $hooks hook name
     */
     protected $hooks = 'AdminAreaHeadOutput';

    /**
     * @InheritDoc
     */
    protected function dispatch($vars) : string
    {
        if (!is_string($vars)) {
            $vars = '';
        }

        // only serve if it was on addon page
        $isAddonPage = $this->getHooksService()->getServices()->getCore()->getAddon()->isAddonPage();
        $depends = [
            HtmlAttributes::buildTag('link', [
                'rel' => 'stylesheet',
                'id' => 'pentagonal-addon-main-css',
                'media' => 'all',
                'href' => URL::addonUrl('/assets/css/pentagonal.css?v=' . Addon::VERSION)
            ]),
            HtmlAttributes::buildTag('script', [
                'id' => 'pentagonal-addon-main-js',
                'async' => true,
                'defer' => true,
                'src' => URL::addonUrl('/assets/js/pentagonal.js?v=' . Addon::VERSION)
            ])
        ];
        if ($isAddonPage) {
            $depends[] = HtmlAttributes::buildTag('script', [
                'id' => 'pentagonal-addon-runtime-js',
                'async' => true,
                'defer' => true,
                'src' => URL::addonUrl('/assets/js/runtime.js?v=' . Addon::VERSION)
            ]);
        }
        return $vars . implode("\n", $depends);
    }
}
