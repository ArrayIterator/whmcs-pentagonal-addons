<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Http\Factory;

use Pentagonal\Neon\WHMCS\Addon\Http\ServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, [], '', '1.1', $serverParams);
    }
}
