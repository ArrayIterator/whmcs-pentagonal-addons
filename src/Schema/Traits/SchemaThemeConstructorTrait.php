<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Traits;

use WHMCS\View\Template\Theme as WhmcsTheme;
use function rtrim;
use function str_replace;
use const DIRECTORY_SEPARATOR;

trait SchemaThemeConstructorTrait
{
    use SchemaTrait;

    /**
     * @var WhmcsTheme REF The schema reference
     */
    protected WhmcsTheme $theme;

    /**
     * @var string $themeDir the parent directory
     */
    protected string $themeDir;

    /**
     * Theme constructor.
     *
     * @param WhmcsTheme $theme
     */
    public function __construct(WhmcsTheme $theme)
    {
        $this->theme = $theme;
        $templatePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $theme->getTemplatePath());
        $this->themeDir = rtrim($templatePath, DIRECTORY_SEPARATOR);
    }

    /**
     * Get WHMCS Theme
     *
     * @return WhmcsTheme
     */
    public function getTheme(): WhmcsTheme
    {
        return $this->theme;
    }

    /**
     * @return string
     */
    public function getThemeDir(): string
    {
        return $this->themeDir;
    }

    /**
     * @inheritdoc
     */
    abstract public function getSchemaFile(): string;
}
