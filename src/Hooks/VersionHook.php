<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Hooks;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractHook;
use Pentagonal\Neon\WHMCS\Addon\Addon;
use function is_array;

/**
 * Version Hook
 * Display the version of the addon
 */
class VersionHook extends AbstractHook
{
    /**
     * @var string $hooks the hook name
     */
    protected $hooks = 'ClientAreaPage';

    /**
     * @inheritDoc
     */
    protected function dispatch($vars)
    {
        if (is_array($vars)) {
            $vars['pentagonalVersion'] = Addon::VERSION;
        }
        return $vars;
    }
}
