<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Traits;

use WHMCS\View\Template\Theme as WhmcsTheme;
use function rtrim;
use function str_replace;
use const DIRECTORY_SEPARATOR;

trait SchemaThemeTrait
{
    use SingleSchemaTrait;

    /**
     * @var string $themeDir the parent directory
     */
    private string $themeDir;

    /**
     * Get WHMCS Theme
     *
     * @return WhmcsTheme
     */
    public function getTheme(): WhmcsTheme
    {
        return $this->getSchemas()->getCore()->getTheme();
    }

    /**
     * @return string
     */
    public function getThemeDir(): string
    {
        if (isset($this->themeDir)) {
            return $this->themeDir;
        }
        $templatePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->getTheme()->getTemplatePath());
        $this->themeDir = rtrim($templatePath, DIRECTORY_SEPARATOR);
        return $this->themeDir;
    }
}
