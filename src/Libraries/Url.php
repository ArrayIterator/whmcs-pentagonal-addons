<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractPlugin;
use Pentagonal\Neon\WHMCS\Addon\Core;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\AdminDispatcher;
use Psr\Http\Message\UriInterface;
use WHMCS\Utility\Environment\WebHelper;
use function ltrim;
use function parse_url;
use function rtrim;
use function trim;
use function urlencode;
use const PHP_URL_PATH;

final class Url
{
    public const ADDON_FILE = 'addonmodules.php';

    /**
     * @var string|null $addonBase the module base
     */
    private ?string $addonBase = null;

    /**
     * @var Core $core the core
     */
    private Core $core;

    /**
     * @var string $adminBasePath the admin base path
     */
    private string $adminBasePath;

    /**
     * @var string $basePath the base path
     */
    private string $basePath;

    public function __construct(Core $core)
    {
        $this->core = $core;
    }

    /**
     * @return Core
     */
    public function getCore(): Core
    {
        return $this->core;
    }

    /**
     * @return UriInterface
     */
    public function getCurrentUri() : UriInterface
    {
        return $this->getCore()->getRequest()->getUri();
    }

    /**
     * Get admin base path
     *
     * @return string
     */
    public function getAdminBasePath() : string
    {
        if (isset($this->adminBasePath)) {
            return $this->adminBasePath;
        }
        $path = '/' . trim(WebHelper::getAdminBaseUrl(), '/');
        return $this->adminBasePath = rtrim((string) parse_url($path, PHP_URL_PATH), '/');
    }

    /**
     * Get base path
     *
     * @return string
     */
    public function getBasePath() : string
    {
        if (isset($this->basePath)) {
            return $this->basePath;
        }
        $path = '/' . trim(WebHelper::getBaseUrl(), '/');
        return $this->basePath = rtrim((string) parse_url($path, PHP_URL_PATH), '/');
    }

    /**
     * Get admin URL
     *
     * @param string $path
     * @return string
     */
    public function getAdminUrl(string $path = ''): string
    {
        return $this->getAdminBasePath() . '/' . ltrim($path, '/');
    }

    /**
     * @return UriInterface
     */
    public function getAdminUri() : UriInterface
    {
        return $this
            ->getCurrentUri()
            ->withQuery('')
            ->withFragment('')
            ->withPath($this->getAdminUrl());
    }

    /**
     * Get module URL
     *
     * @param string $path
     * @return string
     */
    public function getModulesURL(string $path = ''): string
    {
        return $this->getBaseUrl('/modules/') . ltrim($path, '/');
    }

    /**
     * Get modules uri
     *
     * @return UriInterface
     */
    public function getModuleUri() : UriInterface
    {
        return $this
            ->getCurrentUri()
            ->withQuery('')
            ->withFragment('')
            ->withPath($this->getModulesURL());
    }

    /**
     * Get base URL
     *
     * @param string $path
     * @return string
     */
    public function getBaseUrl(string $path = ''): string
    {
        return $this->getBasePath() . '/' . ltrim($path, '/');
    }

    /**
     * Get base URI
     *
     * @return UriInterface
     */
    public function getBaseUri() : UriInterface
    {
        return $this
            ->getCurrentUri()
            ->withQuery('')
            ->withFragment('')
            ->withPath($this->getBaseUrl());
    }

    /**
     * Get addon URL
     *
     * @param string $path
     * @return string
     */
    public function getAddOnsURL(string $path = ''): string
    {
        return $this->getBaseUrl('/modules/addons/') . ltrim($path, '/');
    }

    /**
     * Get addons uri
     *
     * @return UriInterface
     */
    public function getAddonsUri() : UriInterface
    {
        return $this
            ->getCurrentUri()
            ->withQuery('')
            ->withFragment('')
            ->withPath($this->getAddOnsURL());
    }

    /**
     * Get template URL
     *
     * @param string $path
     * @return string
     */
    public function getTemplatesUrl(string $path = ''): string
    {
        return $this->getBaseUrl('/templates/') . ltrim($path, '/');
    }

    /**
     * Get templates URI
     *
     * @return UriInterface
     */
    public function getTemplatesUri() : UriInterface
    {
        return $this
            ->getCurrentUri()
            ->withQuery('')
            ->withFragment('')
            ->withPath($this->getTemplatesUrl());
    }

    /**
     * Get asset URL
     *
     * @param string $path
     * @return string
     */
    public function getAssetUrl(string $path = ''): string
    {
        return $this->getBaseUrl('/assets/') . ltrim($path, '/');
    }

    /**
     * Get assets URI
     *
     * @return UriInterface
     */
    public function getAssetUri() : UriInterface
    {
        return $this
            ->getCurrentUri()
            ->withQuery('')
            ->withFragment('')
            ->withPath($this->getAssetUrl());
    }

    /**
     * Get theme URL
     *
     * @param string $path
     * @return string
     */
    public function getThemeUrl(string $path = ''): string
    {
        $name = $this->getCore()->getTheme()->getName();
        return $this->getBaseUrl('/templates/' . $name . '/') . ltrim($path, '/');
    }

    /**
     * Get theme uri
     *
     * @return UriInterface
     */
    public function getThemeUri() : UriInterface
    {
        return $this
            ->getCurrentUri()
            ->withQuery('')
            ->withFragment('')
            ->withPath($this->getThemeUrl());
    }

    /**
     * Get pentagonal addon URL
     *
     * @param string $path
     * @return string
     */
    public function getAddonUrl(string $path = ''): string
    {
        $this->addonBase ??= $this->getCore()->getAddon()->getAddonName();
        return $this->getBaseUrl('/modules/addons/' . $this->addonBase . '/') . ltrim($path, '/');
    }

    /**
     * Get theme uri
     *
     * @return UriInterface
     */
    public function getAddonUri() : UriInterface
    {
        return $this
            ->getCurrentUri()
            ->withQuery('')
            ->withFragment('')
            ->withPath($this->getAddonUrl());
    }

    /**
     * Get admin addon URL
     *
     * @param string $routePath
     * @return string
     */
    public function getAddonPageUrl(string $routePath = ''): string
    {
        $routePath = trim($routePath);
        if ($routePath) {
            $routePath = '&' . AdminDispatcher::ROUTE_SELECTOR . '=' . urlencode($routePath);
        }
        $name = $this->getCore()->getAddon()->getAddonName();
        return $this->getAdminUrl(self::ADDON_FILE . '?module=' . urlencode($name) . $routePath);
    }

    /**
     * Get Plugin addon url
     *
     * @param AbstractPlugin $plugin
     * @return ?string
     */
    public function getPluginAddonPageUrl(AbstractPlugin $plugin) : ?string
    {
        $path = $this->getCore()->getPlugins()->getPluginPathHash($plugin);
        if (!$path) {
            return null;
        }
        return $this->getAddonPageUrl($path);
    }

    /**
     * Get theme uri
     *
     * @return UriInterface
     */
    public function getAddonPageUri() : UriInterface
    {
        $name = $this->getCore()->getAddon()->getAddonName();
        return $this
            ->getAddonUri()
            ->withPath('/' . self::ADDON_FILE)
            ->withQuery('module=' . urlencode($name));
    }

    /**
     * @param AbstractPlugin $plugin
     * @return string
     */
    public function getPluginUrl(AbstractPlugin $plugin) : string
    {
        $path = $this->getCore()->getPlugins()->getPluginPath($plugin);
        return $this->getBaseUrl($path??'');
    }

    /**
     * Get theme uri
     *
     * @param AbstractPlugin $plugin
     * @return UriInterface
     */
    public function getPluginUri(AbstractPlugin $plugin) : UriInterface
    {
        return $this
            ->getAddonUri()
            ->withPath($this->getPluginUrl($plugin));
    }
}
