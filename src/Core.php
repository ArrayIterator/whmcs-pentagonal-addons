<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Random;
use Pentagonal\Neon\WHMCS\Addon\Http\HttpFactory;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\EventManagerInterface;
use Pentagonal\Neon\WHMCS\Addon\Libraries\EventManager;
use Pentagonal\Neon\WHMCS\Addon\Schema\Schemas;
use Throwable;
use WHMCS\Application;
use WHMCS\View\Template\Theme;
use function in_array;
use const DIRECTORY_SEPARATOR;

class Core
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
     * @var Core $instance the core instance
     */
    private static self $instance;

    /**
     * @var bool $dispatched the core dispatched
     */
    private bool $dispatched = false;

    /**
     * @var EventManager $manager the event manager
     */
    private EventManager $manager;

    /**
     * @var Application $whmcs the whmcs application
     */
    private Application $whmcs;

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
     * Core constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return self
     */
    public static function factory(): self
    {
        return self::$instance ??= new self();
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
        return $this->whmcs ??= Application::getInstance();
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
     * Check if the core is dispatched
     *
     * @return bool
     */
    public function isDispatched(): bool
    {
        return $this->dispatched;
    }

    /**
     * Dispatch the core
     */
    public function dispatch() : self
    {
        if ($this->isDispatched()) {
            return $this;
        }
        // only allow on the addon file
        $file = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file']??null;
        $addonFile = $this->getAddon()->getAddonFile();
        $hooksFile = $this->getAddon()->getAddonDirectory() . DIRECTORY_SEPARATOR . 'hooks.php';
        if (!in_array($file, [$addonFile, $hooksFile])) {
            return $this;
        }
        $stopCode = Random::bytes();
        $profiler = Performance::profile('core_dispatch', Core::class)
            ->setStopCode($stopCode);
        $em = $this->getEventManager();
        Logger::debug('Dispatching Core');
        $this->dispatched = true;
        try {
            try {
                $em->apply(self::EVENT_BEFORE_CORE_DISPATCH, $this);
            } catch (Throwable $e) {
                Logger::error(
                    $e,
                    [
                        'status' => 'error',
                        'method' => 'dispatch',
                        'event' => self::EVENT_BEFORE_CORE_DISPATCH,
                    ]
                );
            }

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
    private function runServices(string $stopCode)
    {
        $performance = Performance::profile('core_dispatch_services', Core::class)
            ->setStopCode($stopCode);
        try {
            $this->getServices()->run();
        } catch (Throwable $e) {
            Logger::error($e, [
                'type' => 'service',
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
        $performance = Performance::profile('core_dispatch_hooks', Core::class)
            ->setStopCode($stopCode);
        try {
            $this->getHooks()->run();
        } catch (Throwable $e) {
            Logger::error($e, [
                'type' => 'service',
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
        return $this->manager ??= new EventManager();
    }
}
