<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Services;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractService;
use Pentagonal\Neon\WHMCS\Addon\Libraries\ThemeOptions;

class ThemeService extends AbstractService
{
    /**
     * @var ThemeOptions $themeOptions the theme options
     */
    protected ThemeOptions $themeOptions;

    /**
     * Get the theme options
     *
     * @return ThemeOptions
     */
    public function getThemeOptions(): ThemeOptions
    {
        if (isset($this->themeOptions)) {
            return $this->themeOptions;
        }
        $core = $this->getServices()->getCore();
        $themeName = $core->getTheme()->getName();
        $optionName = $themeName;
        return $this->themeOptions = new ThemeOptions($optionName);
    }
}
