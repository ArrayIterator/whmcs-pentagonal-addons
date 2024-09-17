<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\EventManagerInterface;
use Pentagonal\Neon\WHMCS\Addon\Libraries\EventManager;
use Pentagonal\Neon\WHMCS\Addon\Libraries\Services;
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
    private static $instance;

    /**
     * @var bool $dispatched the core dispatched
     */
    private $dispatched = false;

    /**
     * @var Services the services
     */
    private $services;

    /**
     * @var EventManager $manager the event manager
     */
    private $manager;

    /**
     * @var Application $whmcs the whmcs application
     */
    private $whmcs;

    /**
     * @var Addon $addon the addon
     */
    private $addon;

    /**
     * Core constructor.
     */
    private function __construct()
    {
        $this->manager = new EventManager();
        $this->services = new Services($this);
        $this->whmcs = Application::getInstance();
        $this->addon = new Addon($this);
    }

    /**
     * @return self
     */
    public static function factory(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
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
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->whmcs;
    }

    /**
     * Get the services object
     *
     * @return Services
     */
    public function getServices(): Services
    {
        return $this->services;
    }

    /**
     * Get the addon object
     *
     * @return Addon
     */
    public function getAddon(): Addon
    {
        return $this->addon;
    }

    /**
     * Dispatch the core
     */
    public function dispatch() : self
    {
        if ($this->dispatched) {
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
            $this->services->run();
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
        return $this->manager;
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
