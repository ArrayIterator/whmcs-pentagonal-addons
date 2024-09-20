<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Http\Factory;

use Pentagonal\Neon\WHMCS\Addon\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = new Response();
        return $response->withStatus($code, $reasonPhrase);
    }
}
