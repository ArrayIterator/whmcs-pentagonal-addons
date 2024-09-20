<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Logger;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Random;
use Pentagonal\Neon\WHMCS\Addon\Helpers\StaticInclude;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\RunnableServiceInterface;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\ServiceInterface;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\ServicesInterface;
use Pentagonal\Neon\WHMCS\Addon\Libraries\Collector;
use Pentagonal\Neon\WHMCS\Addon\Libraries\EventManager;
use Pentagonal\Neon\WHMCS\Addon\Schema\SchemaModel\ThemeSchema;
use Pentagonal\Neon\WHMCS\Addon\Services\AdminService;
use Pentagonal\Neon\WHMCS\Addon\Services\PluginService;
use Pentagonal\Neon\WHMCS\Addon\Services\ThemeService;
use ReflectionClass;
use Throwable;
use function file_exists;

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
        PluginService::class,
        ThemeService::class,
        AdminService::class
    ];

    /**
     * @var array<string, class-string<ServiceInterface|false> $cachedClass the cached class
     */
    private static array $cachedClass = [];

    /**
     * @var class-string<ServiceInterface>[] $protectedServices the protected services
     */
    protected array $protectedServices = self::PROTECTED_SERVICES;

    /**
     * @var Core $core the core instance
     */
    protected Core $core;

    /**
     * @var Collector<ServiceInterface[]> $services the services
     */
    private Collector $services;

    /**
     * @var bool $inProcess the process status
     */
    private bool $inProcess = false;

    /**
     * @var bool $initialized is initialized
     */
    private bool $initialized = false;

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
        $em = $this->getCore()->getEventManager();
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
        return $this->getCore()->getEventManager();
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
        $stopCode = Random::bytes();
        $performance = Performance::profile('services_init', Services::class)
            ->setStopCode($stopCode);
        $this->initialized = true;
        $em = $this->getCore()->getEventManager();
        try {
            try {
                $em->apply(self::EVENT_BEFORE_SERVICES_INIT, $this);
            } catch (Throwable $e) {
                Logger::error($e, [
                    'type' => 'service',
                    'method' => 'init',
                    'event' => self::EVENT_BEFORE_SERVICES_INIT
                ]);
            }
            $themeSchema = $this->getCore()->getSchemas()->get(ThemeSchema::class);
            if (!$themeSchema instanceof ThemeSchema || !$themeSchema->isValid()) {
                return;
            }
            $serviceFile = $themeSchema->getServiceFile();
            if (!$serviceFile || !file_exists($serviceFile)) {
                return;
            }
            $servicePerformance = Performance::profile('services_init_include', Services::class)
                ->setStopCode($stopCode);
            try {
                // no-catch
                StaticInclude::include($serviceFile, ['services' => $this]);
            } finally {
                $servicePerformance->stop([], $stopCode);
            }
        } catch (Throwable $e) {
            Logger::error($e, [
                'type' => 'service',
                'method' => 'init'
            ]);
            try {
                $em->apply(self::EVENT_SERVICE_ERROR, $this, $e);
            } catch (Throwable $e) {
                Logger::error($e, [
                    'type' => 'service',
                    'method' => 'init',
                    'event' => self::EVENT_SERVICE_ERROR
                ]);
            }
        } finally {
            try {
                $em->apply(self::EVENT_AFTER_SERVICES_INIT, $this);
            } catch (Throwable $e) {
                Logger::error($e, [
                    'type' => 'service',
                    'method' => 'init',
                    'event' => self::EVENT_AFTER_SERVICES_INIT
                ]);
            } finally {
                $performance->stop([], $stopCode);
            }
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
