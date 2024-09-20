<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher;

use Pentagonal\Neon\WHMCS\Addon\Addon;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherResponseInterface;
use Pentagonal\Neon\WHMCS\Addon\Extended\AdminLanguage;
use Pentagonal\Neon\WHMCS\Addon\Helpers\ApplicationConfig;
use Pentagonal\Neon\WHMCS\Addon\Helpers\DataNormalizer;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Random;
use Pentagonal\Neon\WHMCS\Addon\Helpers\SessionFlash;
use Pentagonal\Neon\WHMCS\Addon\Libraries\Generator\Menu\Menus;
use Pentagonal\Neon\WHMCS\Addon\Libraries\SmartyAdmin;
use Pentagonal\Neon\WHMCS\Addon\Services\AdminService;
use RuntimeException;
use Throwable;
use WHMCS\Admin;
use function array_key_exists;
use function header;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_encode;
use function ob_clean;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;
use function preg_quote;
use function preg_replace;
use function print_r;
use function realpath;
use function strtolower;
use function trim;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const ROOTDIR;

/**
 * Admin Output - Admin to render the admin module page
 */
class AdminDispatcher
{
    /**
     * @var string PAGE_SELECTOR the page of admin
     */
    public const ROUTE_SELECTOR = 'route';

    /**
     * @var string TYPE_SELECTOR the type of admin page
     */
    public const TYPE_SELECTOR = 'type';

    /**
     * @var string EVENT_ADDON_ADMIN_BEFORE_RENDER before render
     */
    public const EVENT_ADMIN_OUTPUT_DEBUG_API = 'AdminDispatcherOutputDebug';

    /**
     * @var string EVENT_ADMIN_OUTPUT_JSON_OPTION the json_encode(mixed $data, JSON_*);
     */
    public const EVENT_ADMIN_OUTPUT_JSON_OPTION = 'AdminDispatcherOutputJSONOptions';

    /**
     * @var string EVENT_ADMIN_OUTPUT_BEFORE_RENDER is debug
     */
    public const EVENT_ADMIN_OUTPUT_BEFORE_RENDER = 'AdminDispatcherOutputBeforeRender';

    /**
     * @var string EVENT_ADDON_ADMIN_AFTER_RENDER after render
     */
    public const EVENT_ADMIN_OUTPUT_AFTER_RENDER = 'AdminDispatcherOutputAfterRender';

    /**
     * @var string EVENT_ADDON_ADMIN_ON_POST_DATA on post data
     */
    public const EVENT_ADMIN_OUTPUT_API_DATA = 'AdminDispatcherOutputAPIData';

    /**
     * @var SmartyAdmin $smarty the smarty object
     */
    protected SmartyAdmin $smarty;

    /**
     * @var AdminService $adminService the admin service
     */
    protected AdminService $adminService;

    /**
     * @var AdminDispatcherHandler $handler the handler
     */
    protected AdminDispatcherHandler $handler;

    /**
     * @var ?Admin|false $adminObject the admin object
     */
    protected $adminObject = null;

    /**
     * @var bool $dispatched the dispatched
     */
    private bool $dispatched = false;

    /**
     * @var bool $rendered the rendered
     */
    private bool $rendered = false;

    /**
     * @var string|false|null $page
     */
    private $page = null;

    /**
     * @var ?string $type the type
     */
    private ?string $type = null;

    /**
     * @var Menus $topMenu The menus
     */
    private Menus $topMenu;

    /**
     * @var Menus $leftMenu The menus
     */
    private Menus $leftMenu;

    /**
     * @param AdminService $adminService the admin service
     */
    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Get handler
     *
     * @return AdminDispatcherHandler
     */
    public function getHandler() : AdminDispatcherHandler
    {
        return $this->handler ??= new AdminDispatcherHandler($this);
    }

    /**
     * Check if dispatched
     *
     * @return bool
     */
    public function isDispatched(): bool
    {
        return $this->dispatched;
    }

    /**
     * Check if rendered
     *
     * @return bool
     */
    public function isRendered(): bool
    {
        return $this->rendered;
    }

    /**
     * Get top menus
     *
     * @return Menus
     */
    public function getTopMenu(): Menus
    {
        return $this->topMenu ??= new Menus(
            $this->getAdminService()->getServices()->getEventManager()
        );
    }

    /**
     * Get left menus
     *
     * @return Menus
     */
    public function getLeftMenu(): Menus
    {
        return $this->leftMenu ??= new Menus(
            $this->getAdminService()->getServices()->getEventManager()
        );
    }


    /**
     * Get page type
     *
     * @return string|null
     */
    public function getPage() : ?string
    {
        if ($this->page === null) {
            $page = $_GET[self::ROUTE_SELECTOR]??null;
            $this->page = !is_string($page) ? '' : trim($page);
            $this->page = $this->page === '' || strtolower($this->page) === 'index' ? 'index' : $this->page;
        }

        return $this->page === false ? null : $this->page;
    }

    /**
     * Get the type
     *
     * @return string<"api"|"page">
     */
    public function getType() : string
    {
        if ($this->type === null) {
            $type = $_GET[self::TYPE_SELECTOR]??null;
            $this->type = !is_string($type) || strtolower(trim($type)) !== 'api' ? 'page' : 'api';
        }
        return $this->type === 'api' ? 'api' : 'page';
    }

    /**
     * Check if is API
     *
     * @return bool
     */
    public function isApi() : bool
    {
        return $this->getType() === 'api';
    }

    /**
     * Get the admin object
     *
     * @return ?Admin
     */
    public function getAdminObject(): ?Admin
    {
        if ($this->adminObject === null) {
            $this->adminObject = false;
            foreach ($GLOBALS as $value) {
                if ($value instanceof Admin) {
                    $this->adminObject = $value;
                    return $value;
                }
            }
        }

        return $this->adminObject ?: null;
    }

    /**
     * @return AdminService
     */
    public function getAdminService(): AdminService
    {
        return $this->adminService;
    }

    /**
     * Get the smarty object
     *
     * @return SmartyAdmin
     */
    public function getSmarty(): SmartyAdmin
    {
        return $this->smarty ??= new SmartyAdmin(
            $this->getAdminService()->getServices()->getCore()->getAddon()->getAddonDirectory() . '/templates'
        );
    }

    /**
     * Dispatch the event
     *
     * @return void
     */
    public function dispatch()
    {
        $service = $this->getAdminService()->getServices();
        if ($this->dispatched
            || ! $service->getCore()->isAdminAreaRequest()
        ) {
            return;
        }
        $this->dispatched = true;

        // don't process if not addon page
        if (!$this->getAdminService()->getServices()->getCore()->getAddon()->isAddonPage()) {
            return;
        }
        $em = $service->getEventManager();
        $em->attach(Addon::EVENT_ADDON_ADMIN_OUTPUT, [$this, 'render'], true);
    }

    /**
     * Process the api
     *
     * @param $response
     * @param $error
     * @return void
     */
    private function processApi($response, $error)
    {
        $stopCode = Random::bytes();
        $performance = Performance::profile('admin_output_api', AdminDispatcher::class)
            ->setStopCode($stopCode);
        $count = 0;
        // clear output buffer
        while (ob_get_level() > 1 && $count++ < 10) {
            ob_clean();
        }

        if (!$error instanceof Throwable && $response instanceof Throwable) {
            $error = $response;
        }
        $is_debug_part = ApplicationConfig::get('display_errors') === true;
        $is_debug = $is_debug_part;
        $em = $this->getAdminService()->getServices()->getEventManager();
        try {
            $is_debug = $em->apply(self::EVENT_ADMIN_OUTPUT_DEBUG_API, $is_debug);
        } catch (Throwable $e) {
            Logger::error(
                $e,
                [
                    'status' => 'error',
                    'method' => 'processApi',
                    'event' => self::EVENT_ADMIN_OUTPUT_DEBUG_API
                ]
            );
        }
        $jsonOption = JSON_UNESCAPED_SLASHES;
        if ($is_debug) {
            $jsonOption |= JSON_PRETTY_PRINT;
        }
        $originalJsonOption = $jsonOption;
        try {
            $jsonOption = $em->apply(self::EVENT_ADMIN_OUTPUT_JSON_OPTION, $originalJsonOption);
        } catch (Throwable $e) {
            Logger::error(
                $e,
                [
                    'status' => 'error',
                    'method' => 'processApi',
                    'event' => self::EVENT_ADMIN_OUTPUT_JSON_OPTION
                ]
            );
        }

        $jsonOption = !is_int($jsonOption) ? $originalJsonOption : $jsonOption;
        $is_debug = is_bool($is_debug) ? $this : $is_debug_part;
        $rootDir = realpath(ROOTDIR);
        $safeDir = static function (string $content) use ($rootDir) {
            return preg_replace('#^'.preg_quote($rootDir).'#', '[ROOT_DIR]', $content);
        };

        /**
         * @param Throwable $error
         * @param $code
         * @return never-returns
         */
        $serverError = static function (Throwable $error, $code) use ($safeDir, $is_debug, $jsonOption, $performance, $stopCode, $em) {
            header('Content-Type: application/json', true, $code);
            $content = [
                'code' => $code,
                'message' => $safeDir($error->getMessage())
            ];
            if ($is_debug) {
                $traces = [];
                foreach ($error->getTrace() as $trace) {
                    if (count($trace) >= 50) {
                        break;
                    }
                    if (isset($trace['file'])) {
                        $trace['file'] = $safeDir($trace['file']);
                    }
                    $traces[] = $trace;
                }
                $content['trace'] = $traces;
            }
            $performance->stop([
                'error' => $error,
                'code' => $code
            ], $stopCode);
            ob_start();
            try {
                $newContent = $em->apply(self::EVENT_ADMIN_OUTPUT_API_DATA, $content, $code);
                if (!is_array($content) || ($content['code'] ?? null) !== $code || !array_key_exists('message', $content)) {
                    $newContent = $content;
                }
            } catch (Throwable $e) {
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'method' => 'processApi',
                        'event' => self::EVENT_ADMIN_OUTPUT_API_DATA
                    ]
                );
                $newContent = $content;
            }
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            $content = null;
            unset($content);
            echo json_encode($newContent, $jsonOption);
            exit(0);
        };

        /**
         * @param $data
         * @param ?int $code
         * @return never-returns
         */
        $serverSuccess = static function ($data, ?int $code = null) use ($jsonOption, $performance, $stopCode, $em) {
            if ($data instanceof DispatcherResponseInterface) {
                $response = [
                    'code' => $data->getStatusCode(),
                    'data' => $data->getData()
                ];
            } elseif (is_array($data)) {
                $code = $data['code']??null;
                if (!is_int($code) || $code < 100 || $code >= 600 || !array_key_exists('data', $data)) {
                    $code = null;
                }
                $data = count($data) === 2 && $code !== null && array_key_exists('data', $data)
                    ? $data
                    : [
                        'code' => $code??null,
                        'data' => $data
                    ];
                $response = $data;
            } else {
                $code = $code ?? 200;
                $response = [
                    'code' => $code,
                    'data' => $data
                ];
            }
            ob_start();
            $performance->stop([
                'data' => $response['data'],
                'code' => $response['code']
            ], $stopCode);
            try {
                $newData = $em->apply(self::EVENT_ADMIN_OUTPUT_API_DATA, $response, $response['code']);
                if (!is_array($newData) || ($newData['code'] ?? null) !== $response['code'] || !array_key_exists('data', $newData)) {
                    $newData = $response;
                }
            } catch (Throwable $e) {
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'method' => 'processApi',
                        'event' => self::EVENT_ADMIN_OUTPUT_API_DATA
                    ]
                );
                $newData = $response;
            }
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            $response = null;
            unset($response);
            header('Content-Type: application/json', true, $newData['code']);
            echo json_encode($newData, $jsonOption);
            exit(0);
        };

        if ($error instanceof Throwable) {
            $serverError($error, 500);
            // @never
        }
        $code = null;
        if ($response instanceof DispatcherResponseInterface) {
            $code = $response->getStatusCode();
            if ($code >= 400) {
                $error = $response->getError()??new RuntimeException('Unknown Error');
                $serverError($error, $code);
            }
        }
        $serverSuccess($response, $code);
    }

    /**
     * Render output
     *
     * @param $vars
     * @return void
     */
    public function render($vars)
    {
        if ($this->isRendered() || !$this->isDispatched()) {
            return;
        }

        $em = $this->getAdminService()->getServices()->getEventManager();
        if (!$em->is(Addon::EVENT_ADDON_ADMIN_OUTPUT, [$this, 'render'])) {
            return;
        }
        $vars = !is_array($vars) ? $em->getCurrentParam() : $vars;
        if (!is_array($vars)) {
            return;
        }
        $this->rendered = true;
        $admin = $this->getAdminObject();
        if ($admin === null || ! $this->getAdminService()->isAllowedAccessAddonPage()) {
            return;
        }
        $stopCode = Random::bytes();
        $performance = Performance::profile('admin_output', AdminDispatcher::class)
            ->setStopCode($stopCode);
        try {
            $em->apply(self::EVENT_ADMIN_OUTPUT_BEFORE_RENDER, $vars);
        } catch (Throwable $e) {
            Logger::error(
                $e,
                [
                    'status' => 'error',
                    'method' => 'render',
                    'event' => self::EVENT_ADMIN_OUTPUT_BEFORE_RENDER,
                ]
            );
        }

        // disable sidebar
        $admin->sidebar = '';
        $error = null;
        $smarty = $this->getSmarty();
        $vars['session_flash'] = SessionFlash::current();
        $vars['first_activation'] = SessionFlash::get(Addon::SESSION_WELCOME_FLASH_NAME) === true
            && ($_GET['ref']??null) === 'welcome';
        $smarty->assign($vars);
        $content = $this->getHandler()->process($vars, $processed, $error);
        if ($this->isApi()) {
            $this->processApi($content, $error);
            $performance->stop([], $stopCode);
            exit(0); // stop here
        }

        $smarty->assign('error', $error, true);
        $smarty->assign('response_message', $this->getHandler()->getMessage());
        $smarty->assign('top_menu', $this->getTopMenu());
        $smarty->assign('left-menu', $this->getLeftMenu());

        // start buffer
        ob_start();

        // start wrapper
        echo '<div id="pentagonal-addon-section" class="pentagonal-addon-section pentagonal-addon-wait">';
        $please_wait = AdminLanguage::lang('Please wait...');
        echo <<<HTML
<div class="pentagonal-addon-wait-loader">
    <span></span>
    <span></span>
    <span></span>
    <div class="pentagonal-addon-wait-loader-text">
        <span>$please_wait</span>
    </div>
</div>
HTML;

        echo '<div id="pentagonal-addon-container" class="pentagonal-addon-container">';
        try {
            echo '<div id="pentagonal-addon-header" class="pentagonal-addon-header">';
            echo $smarty->fetch('header.tpl');
        } catch (Throwable $e) {
            echo <<<HTML
<div class="alert alert-danger">
    <strong>Header Error:</strong> {$e->getMessage()}
</div>
HTML;
            Logger::error(
                $e,
                [
                    'status' => 'error',
                    'method' => 'render',
                    'event' => Addon::EVENT_ADDON_ADMIN_OUTPUT,
                ]
            );
        } finally {
            // end header
            echo '</div>';
        }

        try {
            echo '<div id="pentagonal-addon-content" class="pentagonal-addon-content">';
            if ($error) {
                $smarty->fetch('error.tpl');
            } else {
                $smarty->assign('content', $content);
                $smarty->fetch('content.tpl');
            }
            echo "<pre>";
//            print_r($performance);
            echo '</pre>';
        } catch (Throwable $e) {
            echo <<<HTML
<div class="alert alert-danger">
    <strong>Content Error:</strong> {$e->getMessage()}
</div>
HTML;
            Logger::error(
                $e,
                [
                    'status' => 'error',
                    'method' => 'render',
                    'event' => Addon::EVENT_ADDON_ADMIN_OUTPUT,
                ]
            );
        } finally {
            // end content
            echo '</div>';
        }

        try {
            echo '<div id="pentagonal-addon-footer" class="pentagonal-addon-footer">';
            echo $smarty->fetch('footer.tpl');
        } catch (Throwable $e) {
            echo <<<HTML
<div class="alert alert-danger">
    <strong>Footer Error:</strong> {$e->getMessage()}
</div>
HTML;
            Logger::error(
                $e,
                [
                    'status' => 'error',
                    'method' => 'render',
                    'event' => Addon::EVENT_ADDON_ADMIN_OUTPUT,
                ]
            );
        } finally {
            // end footer
            echo '</div>';
        }

        // end container
        echo '</div>';
        // end section
        echo '</div>';
        echo DataNormalizer::forceBalanceTags(ob_get_clean());

        $performance->stop([], $stopCode);
        try {
            $em->apply(self::EVENT_ADMIN_OUTPUT_AFTER_RENDER, $vars);
        } catch (Throwable $e) {
            Logger::error(
                $e,
                [
                    'status' => 'error',
                    'method' => 'render',
                    'event' => self::EVENT_ADMIN_OUTPUT_AFTER_RENDER,
                ]
            );
        }
    }
}
