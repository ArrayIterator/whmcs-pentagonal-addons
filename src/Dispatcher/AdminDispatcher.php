<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher;

use Pentagonal\Neon\WHMCS\Addon\Addon;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherResponseInterface;
use Pentagonal\Neon\WHMCS\Addon\Extended\AdminLanguage;
use Pentagonal\Neon\WHMCS\Addon\Helpers\DataNormalizer;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
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
use function ob_get_clean;
use function ob_get_level;
use function ob_start;
use function preg_quote;
use function preg_replace;
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
    public const PAGE_SELECTOR = 'page';

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
    public const EVENT_ADMIN_OUTPUT_ON_POST_DATA = 'AdminDispatcherOutputOnPostData';

    /**
     * @var SmartyAdmin $smarty the smarty object
     */
    protected $smarty;

    /**
     * @var AdminService $adminService the admin service
     */
    protected $adminService;

    /**
     * @var AdminDispatcherHandler $handler the handler
     */
    protected $handler;

    /**
     * @var Admin $adminObject the admin object
     */
    protected $adminObject = null;

    /**
     * @var bool $dispatched the dispatched
     */
    private $dispatched = false;

    /**
     * @var bool $rendered the rendered
     */
    private $rendered = false;

    /**
     * @var string|false|null $page
     */
    private $page = null;

    /**
     * @var string $type the type
     */
    private $type = null;

    /**
     * @var Menus $topMenu The menus
     */
    private $topMenu;

    /**
     * @var Menus $leftMenu The menus
     */
    private $leftMenu;

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
        if (!isset($this->handler)) {
            $this->handler = new AdminDispatcherHandler($this);
        }

        return $this->handler;
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
        if (!(($this->topMenu??null) instanceof Menus)) {
            $this->topMenu = new Menus(
                $this->getAdminService()->getServices()->getEventManager()
            );
        }
        return $this->topMenu;
    }

    /**
     * Get left menus
     *
     * @return Menus
     */
    public function getLeftMenu(): Menus
    {
        if (!(($this->leftMenu??null) instanceof Menus)) {
            $this->leftMenu = new Menus(
                $this->getAdminService()->getServices()->getEventManager()
            );
        }
        return $this->leftMenu;
    }


    /**
     * Get page type
     *
     * @return string|null
     */
    public function getPage() : ?string
    {
        if ($this->page === null) {
            $page = $_GET[self::PAGE_SELECTOR]??null;
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
        if ($this->smarty === null) {
            $this->smarty = new SmartyAdmin(
                $this->adminService->getServices()->getCore()->getAddon()->getAddonDirectory() . '/templates'
            );
        }
        return $this->smarty;
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
        $count = 0;
        // clear output buffer
        while (ob_get_level() > 1 && $count++ < 10) {
            ob_clean();
        }

        if (!$error instanceof Throwable && $response instanceof Throwable) {
            $error = $response;
        }
        $is_debug_part = ($GLOBALS['display_errors']??null) === true;
        $is_debug = $is_debug_part;
        $em = $this->adminService->getServices()->getEventManager();
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
        $serverError = static function (Throwable $error, $code) use ($safeDir, $is_debug, $jsonOption) {
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
            echo json_encode($content, $jsonOption);
            exit(0);
        };

        /**
         * @param $data
         * @param ?int $code
         * @return never-returns
         */
        $serverSuccess = static function ($data, ?int $code = null) use ($jsonOption) {
            if ($data instanceof DispatcherResponseInterface) {
                header('Content-Type: application/json', true, $data->getStatusCode());
                echo json_encode([
                    'code' => $code,
                    'data' => $data
                ], $jsonOption);
                exit(0);
            }
            if (is_array($data)) {
                $code = $data['code']??null;
                if (!is_int($code) || $code < 100 || $code >= 600 || !array_key_exists('data', $data)) {
                    $code = null;
                }
                header('Content-Type: application/json', true, $code??200);
                $data = count($data) === 2 && $code !== null && array_key_exists('data', $data)
                    ? $data
                    : [
                        'code' => $code??null,
                        'data' => $data
                    ];
                echo json_encode($data, $jsonOption);
                exit(0);
            }

            $code = $code??200;
            header('Content-Type: application/json', true, $code);
            echo json_encode([
                'code' => $code,
                'data' => $data
            ], $jsonOption);
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
        if ($this->rendered || !$this->dispatched) {
            return;
        }

        $em = $this->adminService->getServices()->getEventManager();
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
        <span>{$please_wait}</span>
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
        // end container
        echo '</div>';
        // end section
        echo '</div>';
        echo DataNormalizer::forceBalanceTags(ob_get_clean());
    }
}
