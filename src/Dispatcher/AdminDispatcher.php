<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher;

use Pentagonal\Neon\WHMCS\Addon\Addon;
use Pentagonal\Neon\WHMCS\Addon\Core;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Handlers\DispatcherResponse;
use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherResponseInterface;
use Pentagonal\Neon\WHMCS\Addon\Extended\AdminLanguage;
use Pentagonal\Neon\WHMCS\Addon\Helpers\ApplicationConfig;
use Pentagonal\Neon\WHMCS\Addon\Helpers\DataNormalizer;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Profilers\Profiler;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Random;
use Pentagonal\Neon\WHMCS\Addon\Helpers\SessionFlash;
use Pentagonal\Neon\WHMCS\Addon\Http\Code;
use Pentagonal\Neon\WHMCS\Addon\Http\ResponseEmitter;
use Pentagonal\Neon\WHMCS\Addon\Libraries\Generator\Menu\Menus;
use Pentagonal\Neon\WHMCS\Addon\Libraries\SmartyAdmin;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function array_shift;
use function header;
use function headers_sent;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;
use function ob_clean;
use function ob_end_clean;
use function ob_get_length;
use function ob_get_level;
use function ob_start;
use function preg_match;
use function strtolower;
use function trim;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

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
     * @var string EVENT_ADMIN_OUTPUT_API_DATA output data
     */
    public const EVENT_ADMIN_OUTPUT_API_DATA = 'AdminDispatcherOutputAPIData';

    /**
     * @var string EVENT_ADMIN_OUTPUT_API_RESPONSE event response
     */
    public const EVENT_ADMIN_OUTPUT_API_RESPONSE = 'AdminDispatcherOutputAPIResponse';

    /**
     * @var Core $core the core
     */
    protected Core $core;

    /**
     * @var SmartyAdmin $smarty the smarty object
     */
    protected SmartyAdmin $smarty;

    /**
     * @var AdminDispatcherHandler $handler the handler
     */
    protected AdminDispatcherHandler $handler;

    /**
     * @var bool $dispatched the dispatched
     */
    private bool $dispatched = false;

    /**
     * @var bool $rendered the rendered
     */
    private bool $rendered = false;

    /**
     * @var string|false|null $routePath
     */
    private $routePath = null;

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


    public function __construct(Core $core)
    {
        $this->core = $core;
    }

    /**
     * Get core
     *
     * @return Core
     */
    public function getCore(): Core
    {
        return $this->core;
    }

    /**
     * Get handler
     *
     * @return AdminDispatcherHandler
     */
    public function getHandler(): AdminDispatcherHandler
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
            $this->getCore()
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
            $this->getCore()
        );
    }


    /**
     * Get page type
     *
     * @return string|null
     */
    public function getRouteQuery(): ?string
    {
        if ($this->routePath === null) {
            $page = $_GET[self::ROUTE_SELECTOR] ?? null;
            $this->routePath = !is_string($page) ? '' : trim($page);
            $this->routePath = $this->routePath === ''|| strtolower($this->routePath) === 'index'
                ? 'index'
                : $this->routePath;
        }

        return $this->routePath === false ? null : $this->routePath;
    }

    /**
     * Get the type
     *
     * @return string<"api"|"page">
     */
    public function getType(): string
    {
        if ($this->type === null) {
            $type = $_GET[self::TYPE_SELECTOR] ?? null;
            $this->type = !is_string($type) || strtolower(trim($type)) !== 'api' ? 'page' : 'api';
        }
        return $this->type === 'api' ? 'api' : 'page';
    }

    /**
     * Check if is API
     *
     * @return bool
     */
    public function isApiRequest(): bool
    {
        return $this->getType() === 'api';
    }

    /**
     * Get the smarty object
     *
     * @return SmartyAdmin
     */
    public function getSmarty(): SmartyAdmin
    {
        return $this->smarty ??= new SmartyAdmin(
            $this->getCore(),
            $this->getCore()->getAddon()->getAddonDirectory() . '/templates'
        );
    }

    /**
     * Dispatch the event
     *
     * @return void
     */
    public function dispatch()
    {
        // stop if dispatched or is not addon page
        if ($this->isDispatched() || ! $this->getCore()->getAddon()->isAddonPage()) {
            return;
        }
        $this->dispatched = true;
        $this->getCore()->getEventManager()->attach(Addon::EVENT_ADDON_ADMIN_OUTPUT, [$this, 'render'], true);
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
        $performance = Performance::profile('admin_output_api', 'system.admin_dispatcher')
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
        $em = $this->getCore()->getEventManager();
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
        $is_debug = is_bool($is_debug) ? $is_debug : $is_debug_part;
        if ($error instanceof Throwable) {
            $this->serveError(500, $error, $jsonOption, $is_debug, $performance, $stopCode);
            // @never
        }
        $code = null;
        if ($response instanceof ResponseInterface) {
            $body = $response->getBody();
            if (is_array(json_decode((string) $body, true))) {
                if ($body->isSeekable()) {
                    $body->rewind();
                } else {
                    $body = $this
                        ->getCore()
                        ->getHttpFactory()
                        ->getStreamFactory()
                        ->createStream((string) $body);
                    $response = $response->withBody($body);
                }
                $this->emitDataJsonData($response->getStatusCode(), $response, $jsonOption, $performance, $stopCode);
            } else {
                $response = new DispatcherResponse(500, null, new RuntimeException(
                    'Invalid response content from response interface'
                ));
            }
            $code = $response->getStatusCode();
        }
        if ($response instanceof DispatcherResponseInterface) {
            $code = $response->getStatusCode();
            if ($code >= 400) {
                $error = $response->getError() ?? new RuntimeException('Unknown Error');
                $code = Code::statusMessage($code) ? $code : 500;
                $this->serveError($code, $error, $jsonOption, $is_debug, $performance, $stopCode);
            }
        }
        $this->serveSuccess($code??200, $response, $jsonOption, $performance, $stopCode);
    }

    /**
     * @param int $code
     * @param array|ResponseInterface $data
     * @param int $jsonOption
     * @param Profiler $profiler
     * @param string $stopCode
     * @return never-returns
     */
    private function emitDataJsonData(int $code, $data, int $jsonOption, Profiler $profiler, string $stopCode)
    {
        $response = $data instanceof ResponseInterface ? $data : $this
            ->getCore()
            ->getHttpFactory()
            ->getResponseFactory()
            ->createResponse($code)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($data, $jsonOption));
        $em = $this->getCore()->getEventManager();
        try {
            $newResponse = $em->apply(self::EVENT_ADMIN_OUTPUT_API_RESPONSE, $response);
            $response = $newResponse instanceof ResponseInterface ? $newResponse : $response;
        } catch (Throwable $e) {
            Logger::error(
                $e,
                [
                    'status' => 'error',
                    'method' => 'emitDataJsonData',
                    'event' => self::EVENT_ADMIN_OUTPUT_API_RESPONSE
                ]
            );
        }
        $contentType = $response->getHeaderLine('Content-Type');
        if (!preg_match('~^application/json\s*(;|$)~i', $contentType)) {
            $contentType = 'application/json';
        }
        $response = $response->withHeader('Content-Type', $contentType);
        $emitter = new ResponseEmitter();
        try {
            $emitter->emit($response);
            $emitter->close();
        } catch (Throwable $e) {
            if (!headers_sent()) {
                header('Content-Type: ' . $contentType, true, $response->getStatusCode());
            }
            $profiler->stop([
                'code' => $stopCode
            ]);
        }
        exit(0);
    }

    /**
     * @param int $code
     * @param Throwable $data
     * @param int $jsonOption
     * @param bool $is_debug
     * @param Profiler $profiler
     * @param string $stopCode
     * @return never-return
     */
    private function serveError(
        int $code,
        Throwable $data,
        int $jsonOption,
        bool $is_debug,
        Profiler $profiler,
        string $stopCode
    ) {
        $content = [
            'code' => $code,
            'message' => DataNormalizer::protectRootDir($data->getMessage())
        ];
        if ($is_debug) {
            $traces = [];
            foreach ($data->getTrace() as $trace) {
                if (count($trace) >= 50) {
                    break;
                }
                $traces[] = DataNormalizer::protectRootDir($trace);
            }
            $content['trace'] = $traces;
        }
        $profiler->end(false, [
            'error' => $data,
            'code' => $code
        ], $stopCode);
        ob_start();
        $em = $this->getCore()->getEventManager();
        try {
            $newContent = $em->apply(self::EVENT_ADMIN_OUTPUT_API_DATA, $content, $code);
            if (!is_array($content)
                || ($newContent['code']??null) !== $code
                || !array_key_exists('message', $content)) {
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
        $this->emitDataJsonData($code, $newContent, $jsonOption, $profiler, $stopCode);
    }

    /**
     * @param int $code
     * @param $data
     * @param int $jsonOption
     * @param Profiler $performance
     * @param string $stopCode
     * @return never-returns
     */
    private function serveSuccess(int $code, $data, int $jsonOption, Profiler $performance, string $stopCode)
    {
        if ($data instanceof DispatcherResponseInterface) {
            $response = [
                'code' => $data->getStatusCode(),
                'data' => $data->getData()
            ];
        } elseif (is_array($data)) {
            $code = $data['code'] ?? null;
            if (!is_int($code) || $code < 100 || $code >= 600 || !array_key_exists('data', $data)) {
                $code = null;
            }
            $data = count($data) === 2 && $code !== null && array_key_exists('data', $data)
                ? $data
                : [
                    'code' => $code ?? null,
                    'data' => $data
                ];
            $response = $data;
        } else {
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
        $em = $this->getCore()->getEventManager();
        try {
            $newData = $em->apply(self::EVENT_ADMIN_OUTPUT_API_DATA, $response, $response['code']);
            if (!is_array($newData)
                || ($newData['code'] ?? null) !== $response['code']
                || !array_key_exists('data', $newData)
            ) {
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

        $em = $this->getCore()->getEventManager();
        if (!$em->is(Addon::EVENT_ADDON_ADMIN_OUTPUT, [$this, 'render'])) {
            return;
        }
        $vars = !is_array($vars) ? $em->getCurrentParam() : $vars;
        if (!is_array($vars)) {
            return;
        }
        $this->rendered = true;
        $admin = $this->getCore()->getWhmcsAdmin();
        if (!$admin|| ! $this->getCore()->getAddon()->isAllowedAccessAddonPage()) {
            return;
        }
        $stopCode = Random::bytes();
        $performance = Performance::profile('admin_output', 'system.admin_dispatcher')
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

        $html = [];
        // disable sidebar
        $admin->sidebar = '';
        $error = null;
        $smarty = $this->getSmarty();
        $vars['session_flash'] = SessionFlash::current();
        $vars['first_activation'] = SessionFlash::get(Addon::SESSION_WELCOME_FLASH_NAME) === true
            && ($_GET['ref']??null) === 'welcome';
        $smarty->assign($vars);
        $content = $this->getHandler()->process($vars, $processed, $error);
        if ($this->isApiRequest()) {
            $this->processApi($content, $error);
            $performance->stop([], $stopCode);
            exit(0); // stop here
        }

        $smarty->assign('error', $error, true);
        $smarty->assign('response_message', $this->getHandler()->getMessage());
        $smarty->assign('top_menu', $this->getTopMenu());
        $smarty->assign('left-menu', $this->getLeftMenu());

        $level = ob_get_level();
        // start buffer
        ob_start();

        // start wrapper
        $html[] = '<div id="pentagonal-addon-section" class="pentagonal-addon-section pentagonal-addon-wait">';
        $please_wait = AdminLanguage::lang('Please wait...');
        $html[] = (<<<HTML
<div class="pentagonal-addon-wait-loader">
    <span></span>
    <span></span>
    <span></span>
    <div class="pentagonal-addon-wait-loader-text">
        <span>$please_wait</span>
    </div>
</div>
HTML);

        $html[] = ('<div id="pentagonal-addon-container" class="pentagonal-addon-container">');
        try {
            $html[] = ('<div id="pentagonal-addon-header" class="pentagonal-addon-header">');
            $html[] = ($smarty->fetch('header.tpl'));
        } catch (Throwable $e) {
            $html[] = (<<<HTML
<div class="alert alert-danger">
    <strong>Header Error:</strong> {$e->getMessage()}
</div>
HTML);
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
            $html[] = ('</div>');
        }

        try {
            $html[] = ('<div id="pentagonal-addon-content" class="pentagonal-addon-content">');
            if ($error) {
                $html[] = ($smarty->fetch('error.tpl'));
            } else {
                $smarty->assign('content', $content);
                $html[] = ($smarty->fetch('content.tpl'));
            }
        } catch (Throwable $e) {
            $html[] = (<<<HTML
<div class="alert alert-danger">
    <strong>Content Error:</strong> {$e->getMessage()}
</div>
HTML);
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
            $html[] = ('</div>');
        }

        try {
            $html[] = ('<div id="pentagonal-addon-footer" class="pentagonal-addon-footer">');
            $html[] = ($smarty->fetch('footer.tpl'));
        } catch (Throwable $e) {
            $html[] = (<<<HTML
<div class="alert alert-danger">
    <strong>Footer Error:</strong> {$e->getMessage()}
</div>
HTML);
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
            $html[] = ('</div>');
        }

        // end container
        $html[] = ('</div>');
        // end section
        $html[] = ('</div>');
        if (ob_get_length() > 0 || $level < ob_get_level()) {
            ob_end_clean();
            if ($level > ob_get_level()) {
                ob_start();
            }
        }

        while (count($html) > 0) {
            echo array_shift($html) . "\n";
        }
        unset($html);
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
        $performance->stop([], $stopCode);
    }
}
