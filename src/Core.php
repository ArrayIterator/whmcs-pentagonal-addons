<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon;

use Pentagonal\Neon\WHMCS\Addon\Dispatcher\AdminDispatcher;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Random;
use Pentagonal\Neon\WHMCS\Addon\Http\HttpFactory;
use Pentagonal\Neon\WHMCS\Addon\Http\ServerRequest;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\EventManagerInterface;
use Pentagonal\Neon\WHMCS\Addon\Libraries\EventManager;
use Pentagonal\Neon\WHMCS\Addon\Libraries\SmartyAdmin;
use Pentagonal\Neon\WHMCS\Addon\Libraries\ThemeOptions;
use Pentagonal\Neon\WHMCS\Addon\Libraries\Url;
use Pentagonal\Neon\WHMCS\Addon\Schema\Schemas;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use WHMCS\Admin;
use WHMCS\Application;
use WHMCS\View\Template\Theme;
use function array_shift;
use function debug_backtrace;
use function run_hook;
use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const DIRECTORY_SEPARATOR;

/**
 * Core class object for main handler
 */
final class Core
{
    /**
     * @var string EVENT_BEFORE_CORE_DISPATCH before core dispatch
     */
    public const EVENT_BEFORE_CORE_DISPATCH = 'CoreBeforeDispatch';

    /**
     * @var string EVENT_AFTER_CORE_DISPATCH after core dispatch
     */
    public const EVENT_AFTER_CORE_DISPATCH = 'CoreAfterDispatch';

    /**
     * @var bool $dispatched the core dispatched
     */
    private bool $dispatched = false;

    /**
     * @var EventManager $eventManager the event manager
     */
    private EventManager $eventManager;

    /**
     * @var Application $application the whmcs application
     */
    private Application $application;

    /**
     * @var Services the services
     */
    private Services $services;

    /**
     * @var Hooks $hooks
     */
    private Hooks $hooks;

    /**
     * @var Addon $addon the addon
     */
    private Addon $addon;

    /**
     * @var Plugins $plugins the plugins
     */
    private Plugins $plugins;

    /**
     * @var Schemas $schemas the schemas
     */
    private Schemas $schemas;

    /**
     * @var HttpFactory $httpFactory the http-factory
     */
    private HttpFactory $httpFactory;

    /**
     * @var AdminDispatcher $adminDispatcher the admin dispatcher
     */
    private AdminDispatcher $adminDispatcher;

    /**
     * @var Url $url the url helper
     */
    private Url $url;

    /**
     * @var ServerRequestInterface $request the request
     */
    private ServerRequestInterface $request;

    /**
     * @var ThemeOptions $themeOptions the theme options
     */
    private ThemeOptions $themeOptions;

    /**
     * @var SmartyAdmin $smartyAdmin the smarty
     */
    private SmartyAdmin $smartyAdmin;

    /**
     * @var Admin|null|false;
     */
    private $whmcsAdmin = null;

    /**
     * Core constructor.
     */
    private function __construct()
    {
    }

    /**
     * Create the instance
     *
     * @return ?self
     */
    public static function createInstance() : ?self
    {
        $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $file = $debug[0]['file']??null;
        $className = $debug[1]['class']??null;
        if ($file !== __DIR__ . DIRECTORY_SEPARATOR . 'Singleton.php'
            || $className !== Singleton::class
        ) {
            return null;
        }

        return new self();
    }

    /**
     * @return ?Theme
     */
    public function getTheme(): ?Theme
    {
        /**
         * @var Theme $theme
         */
        $theme = $this
            ->getApplication()
            ->getClientAreaTemplate();
        if (!$theme instanceof Theme) {
            return null;
        }
        return $theme;
    }

    /**
     * Get the schemas object
     *
     * @return Schemas
     */
    public function getSchemas() : Schemas
    {
        return $this->schemas ??= new Schemas($this);
    }

    /**
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->application ??= Application::getInstance();
    }

    /**
     * Get the services object
     *
     * @return Services
     */
    public function getServices(): Services
    {
        return $this->services ??= new Services($this);
    }

    /**
     * Get hooks object
     *
     * @return Hooks
     */
    public function getHooks(): Hooks
    {
        return $this->hooks??= new Hooks($this);
    }

    /**
     * Get the addon object
     *
     * @return Addon
     */
    public function getAddon(): Addon
    {
        return $this->addon ??= new Addon($this);
    }

    /**
     * Get plugins object
     *
     * @return Plugins
     */
    public function getPlugins(): Plugins
    {
        return $this->plugins ??= new Plugins($this);
    }

    /**
     * Get http factory
     *
     * @return HttpFactory
     */
    public function getHttpFactory(): HttpFactory
    {
        return $this->httpFactory ??= new HttpFactory();
    }

    /**
     * @return AdminDispatcher
     */
    public function getAdminDispatcher(): AdminDispatcher
    {
        return $this->adminDispatcher ??= new AdminDispatcher($this);
    }

    /**
     * @return Url
     */
    public function getUrl(): Url
    {
        return $this->url ??= new Url($this);
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request ??= ServerRequest::fromGlobals(
            $this->getHttpFactory()->getServerRequestFactory(),
            $this->getHttpFactory()->getStreamFactory()
        );
    }

    /**
     * @return ThemeOptions
     */
    public function getThemeOptions(): ThemeOptions
    {
        return $this->themeOptions ??= new ThemeOptions($this);
    }

    /**
     * Check if the core is dispatched
     *
     * @return bool
     */
    public function isDispatched(): bool
    {
        return $this->dispatched;
    }

    /**
     * Get the admin object, the object get from globals
     *
     * @return ?Admin
     */
    public function getWhmcsAdmin(): ?Admin
    {
        if ($this->whmcsAdmin === null) {
            $this->whmcsAdmin = false;
            if (!$this->isAdminAreaRequest()) {
                return null;
            }
            foreach ($GLOBALS as $value) {
                if ($value instanceof Admin) {
                    $this->whmcsAdmin = $value;
                    return $value;
                }
            }
        }

        return $this->whmcsAdmin ?: null;
    }

    /**
     * Get the smarty object
     *
     * @return SmartyAdmin
     */
    public function getSmartyAdmin(): SmartyAdmin
    {
        if (isset($this->smartyAdmin)) {
            return $this->smartyAdmin;
        }
        $this->smartyAdmin = new SmartyAdmin(
            $this,
            $this->getAddon()->getAddonDirectory() . '/templates'
        );
        $this->smartyAdmin->admin = $this->getWhmcsAdmin();
        return $this->smartyAdmin;
    }

    /**
     * Dispatch the core
     */
    public function dispatch() : self
    {
        if ($this->isDispatched()) {
            return $this;
        }
        $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $function = (array_shift($debug)?:[])['function']??null;
        $className = (array_shift($debug)?:[])['class']??null;
        if ($function !== 'dispatch' || $className !== Singleton::class) {
            return $this;
        }
        $stopCode = Random::bytes();
        $profiler = Performance::profile('core_dispatch', 'system.core')
            ->setStopCode($stopCode);
        $em = $this->getEventManager();
        Logger::debug('Dispatching Core');
        Logger::error(
            new \Exception('saa'),
            [
                'status' => 'error',
                'type' => 'Core',
                'method' => 'dispatch',
                'event' => self::EVENT_BEFORE_CORE_DISPATCH,
            ]
        );
        $this->dispatched = true;
        try {
            try {
                $em->apply(self::EVENT_BEFORE_CORE_DISPATCH, $this);
            } catch (Throwable $e) {
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'type' => 'Core',
                        'method' => 'dispatch',
                        'event' => self::EVENT_BEFORE_CORE_DISPATCH,
                    ]
                );
            }
            $this->runDispatchSystemHook($stopCode);
            $this->runServices($stopCode);
            $this->runHooks($stopCode);
        } finally {
            try {
                $em->apply(self::EVENT_AFTER_CORE_DISPATCH, $this);
            } catch (Throwable $e) {
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'type'  => 'Core',
                        'method' => 'dispatch',
                        'event' => self::EVENT_AFTER_CORE_DISPATCH,
                    ]
                );
            }
            $profiler->stop([], $stopCode);
        }
        return $this;
    }

    /**
     * Run the services
     * @param string $stopCode
     * @return void
     */
    private function runDispatchSystemHook(string $stopCode)
    {
        $performance = Performance::profile('core_dispatch_system_hook', 'system.core')
            ->setStopCode($stopCode);
        try {
            run_hook('PentagonalCoreDispatch', $this, false);
        } catch (Throwable $e) {
            Logger::error($e, [
                'status' => 'error',
                'type' => 'Service',
                'method' => 'run',
            ]);
        } finally {
            $performance->stop([], $stopCode);
        }
    }

    /**
     * Run the services
     * @param string $stopCode
     * @return void
     */
    private function runServices(string $stopCode)
    {
        $performance = Performance::profile('core_dispatch_services', 'system.core')
            ->setStopCode($stopCode);
        try {
            $this->getServices()->run();
        } catch (Throwable $e) {
            Logger::error($e, [
                'status' => 'error',
                'type' => 'Service',
                'method' => 'run',
            ]);
        } finally {
            $performance->stop([], $stopCode);
        }
    }

    /**
     * Run the hooks
     *
     * @return void
     */
    private function runHooks(string $stopCode)
    {
        $performance = Performance::profile('core_dispatch_hooks', 'system.core')
            ->setStopCode($stopCode);
        try {
            $this->getHooks()->run();
        } catch (Throwable $e) {
            Logger::error($e, [
                'status' => 'error',
                'type' => 'Service',
                'method' => 'run',
            ]);
        } finally {
            $performance->stop([], $stopCode);
        }
    }

    /**
     * Check if the request is admin area request
     *
     * @return bool
     */
    public function isAdminAreaRequest(): bool
    {
        return $this->getApplication()->isAdminAreaRequest();
    }

    /**
     * Check if the request is client area request
     *
     * @return bool
     */
    public function isClientAreaRequest(): bool
    {
        return $this->getApplication()->isClientAreaRequest();
    }

    /**
     * Check if the request is api request
     *
     * @return bool
     */
    public function isApiRequest(): bool
    {
        return $this->getApplication()->isApiRequest();
    }

    /**
     * Check if the request is api call
     *
     * @return bool
     */
    public function isApiCall(): bool
    {
        return $this->getApplication()->isApiCall();
    }

    /**
     * Get event manager
     *
     * @return EventManagerInterface
     */
    public function getEventManager(): EventManagerInterface
    {
        return $this->eventManager ??= new EventManager();
    }
}
