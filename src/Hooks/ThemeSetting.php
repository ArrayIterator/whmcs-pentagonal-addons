<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Hooks;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractHook;
use const PHP_INT_MIN;

class ThemeSetting extends AbstractHook
{
    protected int $priority = PHP_INT_MIN;

    /**
     * @var string[] $hooks hook name
     */
    protected $hooks = [
        'ClientAreaPage',
        'AdminAreaPage',
    ];

    /**
     * @InheritDoc
     */
    protected function dispatch($vars)
    {
        return $vars;
    }
}
