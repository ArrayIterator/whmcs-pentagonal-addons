<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Http\Factory;

use Pentagonal\Neon\WHMCS\Addon\Http\Uri;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class UriFactory implements UriFactoryInterface
{
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}
