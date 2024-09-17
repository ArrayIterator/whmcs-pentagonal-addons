<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

use Pentagonal\Neon\WHMCS\Addon\Core;
use Pentagonal\Neon\WHMCS\Addon\Libraries\EventManager;

/**
 * @template T of ServiceInterface
 */
interface ServicesInterface
{
    /**
     * ServicesInterface constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core);

    /**
     * @return Core
     */
    public function getCore(): Core;

    /**
     * Get event manager
     *
     * @return EventManager
     */
    public function getEventManager(): EventManager;

    /**
     * Add the service
     *
     * @param class-string<T>|T $serviceOrClassName
     */
    public function add($serviceOrClassName);

    /**
     * Get service
     *
     * @param class-string<T>|T $serviceOrClassName
     * @return ?T
     */
    public function get($serviceOrClassName): ?ServiceInterface;

    /**
     * Check if the services has service
     *
     * @param class-string<T>|T $serviceOrClassName
     * @return bool
     */
    public function has($serviceOrClassName): bool;

    /**
     * Remove service by service object or id
     *
     * @param class-string<T>|T $serviceOrClassName
     * @return ServiceInterface|null returning service interface if found
     */
    public function remove($serviceOrClassName): ?ServiceInterface;

    /**
     * Run the services
     */
    public function run();
}
