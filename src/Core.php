<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\EventManagerInterface;
use Pentagonal\Neon\WHMCS\Addon\Libraries\EventManager;
use Pentagonal\Neon\WHMCS\Addon\Libraries\Services;
use Pentagonal\Neon\WHMCS\Addon\Schema\Schemas;
use Throwable;
use WHMCS\Application;
use WHMCS\View\Template\Theme;
use function get_class;
use function get_object_vars;
use function in_array;
use function spl_object_hash;
use function sprintf;
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
     * @var Services the services
     */
    private Services $services;

    /**
     * @var EventManager $manager the event manager
     */
    private EventManager $manager;

    /**
     * @var Application $whmcs the whmcs application
     */
    private Application $whmcs;

    /**
     * @var Addon $addon the addon
     */
    private Addon $addon;

    /**
     * @var Schemas $schemas the schemas
     */
    private Schemas $schemas;

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
     * Get the addon object
     *
     * @return Addon
     */
    public function getAddon(): Addon
    {
        return $this->addon ??= new Addon($this);
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
            $this->getServices()->run();
        } catch (Throwable $e) {
            Logger::error(
                $e,
                [
                    'status' => 'error',
                    'method' => 'dispatch',
                ]
            );
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
        }
        return $this;
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

    /**
     * Debug info
     *
     * @return array
     */
    public function __debugInfo() : array
    {
        $object = get_object_vars($this);
        $object['whmcs'] = sprintf('%s(%s)', get_class($object['whmcs']), spl_object_hash($object['whmcs']));
        $object['addon'] = sprintf('%s(%s)', get_class($object['addon']), spl_object_hash($object['addon']));
        return $object;
    }
}
