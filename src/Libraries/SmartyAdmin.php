<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries;

use Exception;
use Pentagonal\Neon\WHMCS\Addon\Addon;
use Pentagonal\Neon\WHMCS\Addon\Core;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\UnprocessableException;
use Pentagonal\Neon\WHMCS\Addon\Extended\AdminLanguage;
use Pentagonal\Neon\WHMCS\Addon\Helpers\ApplicationConfig;
use Pentagonal\Neon\WHMCS\Addon\Helpers\DataNormalizer;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Options;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Random;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\EventManagerInterface;
use Smarty;
use SmartyBC;
use Throwable;
use WHMCS\Admin;
use WHMCS\Application\Support\Facades\Di;
use function function_exists;
use function is_dir;
use function is_string;
use function ob_end_clean;
use function ob_get_length;
use function ob_get_level;
use function ob_start;
use function preg_match;
use function realpath;
use function sprintf;
use const SMARTY_MBSTRING;

class SmartyAdmin extends SmartyBC
{
    public const EVENT_SMARTY_ADMIN_HEADER = 'SmartyAdminHeader';
    public const EVENT_SMARTY_ADMIN_FOOTER = 'SmartyAdminFooter';
    public const EVENT_SMARTY_ADMIN_CONTENT = 'SmartyAdminContent';

    /**
     * @var string $template the template
     */
    private string $template;

    /**
     * @var bool $populated is populated
     */
    private bool $populated = false;

    /**
     * @var bool $processingOutput the process output
     */
    private bool $processingOutput = false;

    /**
     * @var ?Admin $admin the admin
     */
    public ?Admin $admin = null;

    /**
     * @var Core $core the core
     */
    private Core $core;

    /**
     * @var string $contentTemplate the content template
     */
    public string $contentTemplate = 'content.tpl';

    /**
     * @var string $footerTemplate the footer template
     */
    private string $footerTemplate = 'footer.tpl';

    /**
     * @var string $headerTemplate the header template
     */
    private string $headerTemplate = 'header.tpl';

    /**
     * @var array<string> $preLoadTemplates
     */
    private array $preLoadTemplates = [
        'preload.tpl',
        'pre-load.tpl',
        'pre_load.tpl',
    ];

    /**
     * @var array<string> $postLoadTemplates
     */
    private array $postLoadTemplates = [
        'postload.tpl',
        'post-load.tpl',
        'post_load.tpl',
    ];

    /**
     * @param string $templatesDir Template Directory
     */
    public function __construct(
        Core $core,
        string $templatesDir
    ) {
        $this->core = $core;
        self::$_MBSTRING = SMARTY_MBSTRING && function_exists("mb_split");
        parent::__construct();

        $this->setCaching(Smarty::CACHING_OFF);
        $directory = ApplicationConfig::get("templates_compiledir");
        if (is_string($directory)) {
            $this->setCompileDir($directory);
        }

        $admin_template = Options::get('admin_template');
        $original_template = $admin_template;
        $admin_template = is_string($admin_template) ? trim($admin_template) : null;
        $admin_template = is_string($admin_template)
            && $admin_template !== ''
            && !preg_match('~[a-zA-Z_-]~', $admin_template) ? $admin_template : null;
        if (!is_string($admin_template)) {
            $admin_template = 'pentagonal';
        }

        $templatesDir = realpath($templatesDir)?:$templatesDir;
        $templatesDir = rtrim(DataNormalizer::makeUnixSeparator($templatesDir), '/');
        if ($admin_template !== 'pentagonal' && !is_dir("$templatesDir/$admin_template")) {
            $admin_template = 'pentagonal'; // fallback
        }
        if ($original_template !== $admin_template) {
            Options::set('admin_template', $admin_template);
        }
        $templatesDir = [
            "$templatesDir/$admin_template",
            $templatesDir
        ];
        $this->template = $admin_template;
        $this->setTemplateDir($templatesDir);
    }

    /**
     * @return Core
     */
    public function getCore(): Core
    {
        return $this->core;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return bool
     */
    public function isProcessingOutput(): bool
    {
        return $this->processingOutput;
    }

    /**
     * @return bool
     */
    public function isPopulated(): bool
    {
        return $this->populated;
    }

    public function getFooterTemplate(): string
    {
        return $this->footerTemplate;
    }

    public function setFooterTemplate(string $footerTemplate): void
    {
        if ($this->isProcessingOutput()) {
            return;
        }
        $this->footerTemplate = $footerTemplate;
    }

    public function getHeaderTemplate(): string
    {
        return $this->headerTemplate;
    }

    public function setHeaderTemplate(string $headerTemplate): void
    {
        if ($this->isProcessingOutput()) {
            return;
        }
        $this->headerTemplate = $headerTemplate;
    }

    public function getContentTemplate(): string
    {
        return $this->contentTemplate;
    }

    public function setContentTemplate(string $contentTemplate): void
    {
        if ($this->isProcessingOutput()) {
            return;
        }
        $this->contentTemplate = $contentTemplate;
    }

    /**
     * Assign Default Variables
     *
     * @return void
     */
    public function populateVariables(bool $repopulate = false)
    {
        if (!$repopulate && $this->isPopulated()) {
            return;
        }

        $this->populated = true;
        $this->admin ??= $this->getCore()->getWhmcsAdmin();
        if ($this->admin) {
            $admin = clone $this->admin;
            $admin->populateStandardAdminSmartyVariables();
            $admin->templatevars['admin_template'] = $admin->templatevars['template']??'blend';
            $this->assign($admin->templatevars);
        }
        $url = $this->getCore()->getUrl();
        $this->assign([
            'template' => $this->getTemplate(),
            'addon_version' => Addon::VERSION,
            'addon_url' => $url->getAddonUrl(),
            'addons_url' => $url->getAddOnsURL(),
            'admin_url' => $url->getAdminUrl(),
            'base_url' => $url->getBaseUrl(),
            'theme_url' => $url->getThemeUrl(),
            'templates_url' => $url->getTemplatesUrl(),
            'asset_url' => $url->getAssetUrl(),
            'modules_url' => $url->getModulesURL(),
        ]);
        $functions = [
            'addon_url' => [$url, 'getAddonUrl'],
            'addons_url' => [$url, 'getAddOnsURL'],
            'admin_url' => [$url, 'getAdminUrl'],
            'base_url' => [$url, 'getBaseUrl'],
            'theme_url' => [$url, 'getThemeUrl'],
            'templates_url' => [$url, 'getTemplatesUrl'],
            'asset_url' => [$url, 'getAssetUrl'],
            'modules_url' => [$url, 'getModulesURL'],
        ];
        foreach ($functions as $name => $callback) {
            try {
                $this->registerPlugin(Smarty::PLUGIN_FUNCTION, $name, function ($args) use ($callback) {
                    $path = $args['path']??null;
                    return $callback((string) $path);
                });
            } catch (Throwable $e) {
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'type' => 'SmartyAdmin',
                        'method' => 'assignDefault',
                        'smarty_function' => $name
                    ]
                );
                // pass
            }
        }
        try {
            if (!isset($this->registered_plugins[Smarty::PLUGIN_MODIFIER]['sprintf2'])) {
                $this->registerPlugin(
                    Smarty::PLUGIN_MODIFIER,
                    "sprintf2",
                    ["WHMCS\\Smarty", "sprintf2Modifier"]
                );
            }
            if (!isset($this->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'])) {
                $this->registerPlugin(Smarty::PLUGIN_FUNCTION, "lang", ["WHMCS\\Smarty", "langFunction"]);
            }
            if (!isset($this->registered_plugins[Smarty::FILTER_PRE]["WHMCS\\Smarty"])) {
                $this->registerFilter(
                    Smarty::FILTER_PRE,
                    ["WHMCS\\Smarty", "preFilterSmartyTemplateVariableScopeResolution"]
                );
            }
            $policy = Di::getFacadeApplication()->make("WHMCS\\Smarty\\Security\\Policy", [$this, 'system']);
            $this->enableSecurity($policy);
        } catch (Exception $e) {
            $this->trigger_error($e->getMessage());
        }
    }

    /**
     * Get output
     *
     * @param bool $repopulate
     * @return string
     * @throws UnprocessableException
     */
    public function output(bool $repopulate = false) : string
    {
        if ($this->isProcessingOutput()) {
            throw new UnprocessableException(
                'Output still processing!'
            );
        }

        $this->processingOutput = true;
        try {
            $em = $this->getCore()->getEventManager();
            $stopCode = Random::bytes();
            $performance = Performance::profile('output', 'system.smarty_admin')
                ->setStopCode($stopCode);
            $this->populateVariables($repopulate);
            $this->assign('template_file', $this->getContentTemplate());
            $level = ob_get_level();
            // start buffer
            ob_start();
            // start wrapper
            $html = '<div id="pentagonal-addon-section" class="pentagonal-addon-section pentagonal-addon-wait">';
            $please_wait = AdminLanguage::lang('Please wait...');
            $html .= (<<<HTML
<div class="pentagonal-addon-wait-loader">
    <span></span>
    <span></span>
    <span></span>
    <div class="pentagonal-addon-wait-loader-text">
        <span>$please_wait</span>
    </div>
</div>
HTML);
            try {
                $renderedPreLoad = false;
                foreach ($this->preLoadTemplates as $preLoadTemplate) {
                    if ($this->templateExists($preLoadTemplate)) {
                        Logger::debug(sprintf('Loading pre load template: %s', $preLoadTemplate), [
                            'template' => $preLoadTemplate
                        ]);
                        $html .= $this->fetch($preLoadTemplate);
                        $renderedPreLoad = true;
                        break;
                    }
                }
                if (!$renderedPreLoad) {
                    Logger::debug('Preload is not rendered');
                }
            } catch (Throwable $e) {
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'type' => 'SmartyAdmin',
                        'method' => 'output',
                        'template' => $preLoadTemplate ?? null
                    ]
                );
            }

            $html .= ('<div id="pentagonal-addon-container" class="pentagonal-addon-container">');
            $headerTemplate = $this->getHeaderTemplate();
            try {
                $html .= ('<div id="pentagonal-addon-header" class="pentagonal-addon-header">');
                if ($this->templateExists($this->headerTemplate)) {
                    $html .= DataNormalizer::bufferedCall(function (
                        string $stopCode,
                        EventManagerInterface $em,
                        string $headerTemplate
                    ) {
                        Logger::debug(sprintf('Loading header template: %s', $headerTemplate), [
                            'template' => $headerTemplate
                        ]);
                        $content = $this->fetch($headerTemplate);
                        $performance = Performance::profile('fetch_header', 'smart.smarty_admin')
                            ->setData([
                                'template' => $headerTemplate
                            ])
                            ->setStopCode($stopCode);
                        try {
                            $newHeader = $em->apply(self::EVENT_SMARTY_ADMIN_HEADER, $content, $this);
                            $content = is_string($newHeader) ? $newHeader : $content;
                            $newHeader = null;
                            unset($newHeader);
                        } catch (Throwable $e) {
                            Logger::error(
                                $e,
                                [
                                    'status' => 'error',
                                    'type' => 'SmartyAdmin',
                                    'method' => 'output',
                                    'event' => self::EVENT_SMARTY_ADMIN_HEADER
                                ]
                            );
                        } finally {
                            $performance->stop([], $stopCode);
                        }
                        return $content;
                    }, $stopCode, $em, $headerTemplate);
                }
            } catch (Throwable $e) {
                $html .= (<<<HTML
<div class="alert alert-danger">
    <strong>Header Error:</strong> {$e->getMessage()}
</div>
HTML);
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'type' => 'SmartyAdmin',
                        'method' => 'output',
                        'template' => $headerTemplate
                    ]
                );
            } finally {
                // end header
                $html .= ('</div>');
            }

            $html .= ('<div id="pentagonal-addon-content" class="pentagonal-addon-content">');
            $contentTemplate = $this->getContentTemplate();
            try {
                if ($this->templateExists($contentTemplate)) {
                    $html .= DataNormalizer::bufferedCall(function (
                        string $stopCode,
                        EventManagerInterface $em,
                        string $contentTemplate
                    ) {
                        Logger::debug(sprintf('Loading content template: %s', $contentTemplate), [
                            'template' => $contentTemplate
                        ]);
                        $content = $this->fetch($contentTemplate);
                        $performance = Performance::profile('fetch_content', 'smart.smarty_admin')
                            ->setData([
                                'template' => $contentTemplate,
                            ])
                            ->setStopCode($stopCode);
                        try {
                            $newContent = $em->apply(self::EVENT_SMARTY_ADMIN_CONTENT, $content, $this);
                            $content = is_string($newContent) ? $newContent : $content;
                            $newContent = null;
                            unset($newContent);
                        } catch (Throwable $e) {
                            Logger::error(
                                $e,
                                [
                                    'status' => 'error',
                                    'type' => 'SmartyAdmin',
                                    'method' => 'output',
                                    'event' => self::EVENT_SMARTY_ADMIN_CONTENT
                                ]
                            );
                        } finally {
                            $performance->stop([], $stopCode);
                        }
                        return $content;
                    }, $stopCode, $em, $contentTemplate);
                }
            } catch (Throwable $e) {
                $html .= (<<<HTML
<div class="alert alert-danger">
    <strong>Content Error:</strong> {$e->getMessage()}
</div>
HTML);
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'type' => 'SmartyAdmin',
                        'method' => 'output',
                        'template' => $contentTemplate
                    ]
                );
            } finally {
                // end content
                $html .= ('</div>');
            }

            $html .= ('<div id="pentagonal-addon-footer" class="pentagonal-addon-footer">');
            $footerTemplate = $this->getFooterTemplate();
            try {
                if ($this->templateExists($footerTemplate)) {
                    $html .= DataNormalizer::bufferedCall(function (
                        string $stopCode,
                        EventManagerInterface $em,
                        string $footerTemplate
                    ) {
                        Logger::debug(sprintf('Loading footer template: %s', $footerTemplate), [
                            'template' => $footerTemplate
                        ]);
                        $content = $this->fetch($this->footerTemplate);
                        $performance = Performance::profile('fetch_footer', 'smart.smarty_admin')
                            ->setData([
                                'template' => 'footer.tpl'
                            ])
                            ->setStopCode($stopCode);
                        try {
                            $newFooter = $em->apply(self::EVENT_SMARTY_ADMIN_FOOTER, $content, $this);
                            $content = is_string($newFooter) ? $newFooter : $content;
                            $newFooter = null;
                            unset($newFooter);
                        } catch (Throwable $e) {
                            Logger::error(
                                $e,
                                [
                                    'status' => 'error',
                                    'type' => 'SmartyAdmin',
                                    'method' => 'output',
                                    'event' => self::EVENT_SMARTY_ADMIN_FOOTER
                                ]
                            );
                        } finally {
                            $performance->stop([], $stopCode);
                        }
                        return $content;
                    }, $stopCode, $em, $footerTemplate);
                }
            } catch (Throwable $e) {
                $html .= (<<<HTML
<div class="alert alert-danger">
    <strong>Footer Error:</strong> {$e->getMessage()}
</div>
HTML);
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'type' => 'SmartyAdmin',
                        'method' => 'output',
                        'template' => $footerTemplate
                    ]
                );
            } finally {
                // end footer
                $html .= ('</div>');
            }

            // end container
            $html .= ('</div>');
            // end section
            $html .= ('</div>');
            try {
                $renderedPostLoad = false;
                foreach ($this->postLoadTemplates as $postLoadTemplate) {
                    if ($this->templateExists($postLoadTemplate)) {
                        Logger::debug(sprintf('Loading post load template: %s', $postLoadTemplate), [
                            'template' => $postLoadTemplate
                        ]);
                        $html .= $this->fetch($postLoadTemplate);
                        $renderedPostLoad = true;
                        break;
                    }
                }
                if (!$renderedPostLoad) {
                    Logger::debug('Postload is not rendered');
                }
            } catch (Throwable $e) {
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'type' => 'SmartyAdmin',
                        'method' => 'output',
                        'template' => $postloadTemplate ?? null
                    ]
                );
            }
            if (ob_get_length() > 0 || $level < ob_get_level()) {
                ob_end_clean();
                if ($level > ob_get_level()) {
                    ob_start();
                }
            }
            $performance->stop([], $stopCode);
        } finally {
            $this->processingOutput = false;
        }
        return $html;
    }
}
