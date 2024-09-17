<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Libraries;

use Pentagonal\Neon\WHMCS\Addon\Core;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\ServiceInclude;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\RunnableServiceInterface;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\ServiceInterface;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\ServicesInterface;
use Pentagonal\Neon\WHMCS\Addon\Services\AdminService;
use Pentagonal\Neon\WHMCS\Addon\Services\Hooks;
use ReflectionClass;
use Throwable;
use WHMCS\View\Template\Theme;

class Services implements ServicesInterface
{
    /**
     * @var string EVENT_BEFORE_SERVICES_INIT The before services init event
     */
    public const EVENT_BEFORE_SERVICES_INIT = 'ServicesBeforeInit';

    /**
     * @var string EVENT_AFTER_SERVICES_INIT The after services init event
     */
    public const EVENT_AFTER_SERVICES_INIT = 'ServicesAfterInit';

    /**
     * @var string EVENT_BEFORE_SERVICES_RUN The before services run event
     */
    public const EVENT_BEFORE_SERVICES_RUN = 'ServicesBeforeRun';

    /**
     * @var string EVENT_AFTER_SERVICES_RUN The after services run event
     */
    public const EVENT_AFTER_SERVICES_RUN = 'ServicesAfterRun';

    /**
     * @var string EVENT_SERVICE_ERROR The service error event
     */
    public const EVENT_SERVICE_ERROR = 'ServiceError';

    /**
     * @var string EVENT_SERVICES_ERROR The services error event
     */
    public const EVENT_SERVICES_ERROR = 'ServicesError';

    /**
     * @var string EVENT_BEFORE_SERVICE_RUN The before service run event
     */
    public const EVENT_BEFORE_SERVICE_RUN = 'ServiceBeforeRun';

    /**
     * @var string EVENT_AFTER_SERVICE_RUN The after service run event
     */

    public const EVENT_AFTER_SERVICE_RUN = 'ServiceAfterRun';

    /**
     * @var class-string<ServiceInterface>[]
     */
    public const PROTECTED_SERVICES = [
        AdminService::class,
        Hooks::class
    ];

    /**
     * @var array<string, class-string<ServiceInterface|false> $cachedClass the cached class
     */
    private static $cachedClass = [];

    /**
     * @var class-string<ServiceInterface>[] $protectedServices the protected services
     */
    protected $protectedServices = self::PROTECTED_SERVICES;

    /**
     * @var Core $core the core instance
     */
    protected $core;

    /**
     * @var Collector<ServiceInterface[]> $services the services
     */
    private $services;

    /**
     * @var bool $inProcess the process status
     */
    private $inProcess = false;

    /**
     * @var bool $initialized is initialized
     */
    private $initialized = false;

    /**
     * @inheritDoc
     */
    final public function __construct(Core $core)
    {
        $this->core = $core;
        $this->services = new Collector();
        foreach (self::PROTECTED_SERVICES as $service) {
            $this->add($service);
        }
    }

    /**
     * @inheritDoc
     */
    final public function add($serviceOrClassName)
    {
        if ($serviceOrClassName instanceof ServiceInterface) {
            $className = get_class($serviceOrClassName);
            if (isset($this->services[$className]) && in_array($className, $this->protectedServices, true)) {
                return;
            }
            $this->services[$className] = $serviceOrClassName;
            return;
        }
        $className = $this->getServiceClassName($serviceOrClassName);
        if (!$className) {
            return;
        }
        try {
            $this->add(new $serviceOrClassName($this));
        } catch (Throwable $e) {
            Logger::error($e, [
                'type' => 'service',
                'method' => 'add',
                'service' => $serviceOrClassName
            ]);
        }
    }

    /**
     * Get the service class name
     *
     * @template TClass ServiceInterface
     * @param class-string<TClass>|TClass|mixed $param
     * @return ?class-string<TClass>
     */
    final public function getServiceClassName($param): ?string
    {
        if (is_object($param)) {
            return $param instanceof ServiceInterface ? get_class($param) : null;
        }
        /** @noinspection DuplicatedCode */
        if (!is_string($param)) {
            return null;
        }
        $param = trim($param);
        if (!$param) {
            return null;
        }

        // clear cache
        while (count(self::$cachedClass) > 1000) {
            array_shift(self::$cachedClass);
        }

        $lower = strtolower($param);
        $className = self::$cachedClass[$lower] ?? null;
        if ($className !== null) {
            return $className ?: null;
        }
        self::$cachedClass[$lower] = false;
        try {
            $ref = new ReflectionClass($param);
            $className = $ref->getName();
            $lowerClassName = strtolower($className);
            self::$cachedClass[$lowerClassName] = false;
            if (!$ref->implementsInterface(ServiceInterface::class)) {
                return null;
            }
            self::$cachedClass[strtolower($className)] = $className;
            self::$cachedClass[$lower] = $className;
        } catch (Throwable $e) {
            return null;
        }
        return $className;
    }

    /**
     * @InheritDoc
     */
    public function has($serviceOrClassName): bool
    {
        return (bool)$this->get($serviceOrClassName);
    }

    /**
     * @InheritDoc
     */
    public function get($serviceOrClassName): ?ServiceInterface
    {
        $className = $this->getServiceClassName($serviceOrClassName);
        if (!$className) {
            return null;
        }
        return $this->services[$className] ?? null;
    }

    /**
     * @InheritDoc
     */
    public function remove($serviceOrClassName): ?ServiceInterface
    {
        $service = $this->get($serviceOrClassName);
        if ($service) {
            $className = get_class($service);
            if (in_array($className, $this->protectedServices, true)) {
                return null;
            }
            unset($this->services[$className]);
        }
        return $service;
    }

    /**
     * @InheritDoc
     */
    public function run()
    {
        if ($this->inProcess) {
            return;
        }
        $em = $this->core->getEventManager();
        $this->inProcess = true;
        $this->init();
        try {
            $em->apply(self::EVENT_BEFORE_SERVICES_RUN, $this);
            foreach ($this->services as $service) {
                if ($service instanceof RunnableServiceInterface) {
                    try {
                        $em->apply(self::EVENT_BEFORE_SERVICE_RUN, $service);
                        $service->run();
                        $em->apply(self::EVENT_AFTER_SERVICE_RUN, $service);
                    } catch (Throwable $e) {
                        Logger::error($e, [
                            'type' => 'service',
                            'method' => 'run',
                            'service' => get_class($service)
                        ]);
                        $em->apply(self::EVENT_SERVICE_ERROR, $service, $e);
                    }
                }
            }
        } catch (Throwable $e) {
            Logger::error($e, [
                'type' => 'service',
                'method' => 'run'
            ]);
            $em->apply(self::EVENT_SERVICES_ERROR, $this, $e);
        } finally {
            $em->apply(self::EVENT_AFTER_SERVICES_RUN, $this);
            $this->inProcess = false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getEventManager(): EventManager
    {
        return $this->core->getEventManager();
    }

    /**
     * Initialize
     *
     * @return void
     * @private
     */
    private function init(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        $em = $this->core->getEventManager();
        try {
            $em->apply(self::EVENT_BEFORE_SERVICES_INIT, $this);
            $theme = $this->getCore()->getTheme();
            if (!$theme instanceof Theme) {
                return;
            }
            $activeTemplate = $theme->getTemplatePath();
            if (!is_dir($activeTemplate)) {
                return;
            }
            // no-catch
            ServiceInclude::include($this, $activeTemplate . '/services.php');
        } catch (Throwable $e) {
            Logger::error($e, [
                'type' => 'service',
                'method' => 'init'
            ]);
            $em->apply(self::EVENT_SERVICE_ERROR, $this, $e);
        } finally {
            $em->apply(self::EVENT_AFTER_SERVICES_INIT, $this);
        }
    }

    /**
     * @inheritDoc
     */
    public function getCore(): Core
    {
        return $this->core;
    }
}
