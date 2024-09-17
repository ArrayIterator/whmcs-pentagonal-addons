<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Extended;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractExtended;
use function strtolower;

/**
 * Class to handle the uri path
 *
 * @mixin \WHMCS\Route\UriPath
 *
 * @method static \WHMCS\Route\UriPath getFacadeApplication()
 * @method static \WHMCS\Route\UriPath facadeApplication()
 * @method static \WHMCS\Route\UriPath getAccessor()
 * @method static \WHMCS\Route\UriPath accessor()
 * @method static string getPath(string $routeName, ...$routeVars)
 * @method static string path(string $routeName, ...$routeVars)
 * @method static string getRawPath(string $routeName, $routeVars = [])
 * @method static string rawPath(string $routeName, $routeVars = [])
 */
class UriPath extends AbstractExtended
{
    /**
     * @inheritDoc
     */
    protected function getFacadeName(): string
    {
        return 'Route\UriPath';
    }

    /**
     * @inheritDoc
     */
    protected function magicCaller(string $name, array $arguments)
    {
        switch (strtolower($name)) {
            case 'getpath':
            case 'path':
                return $this->getFacadeApplication()->getPath(...$arguments);
            case 'getrawpath':
            case 'rawpath':
                return $this->getFacadeApplication()->getRawPath(...$arguments);
        }
        return parent::magicCaller($name, $arguments);
    }

    /**
     * @inheritDoc
     * @return \WHMCS\Route\UriPath
     */
    protected function getFacadeApplication(): \WHMCS\Route\UriPath
    {
        return parent::getFacadeApplication();
    }
}
