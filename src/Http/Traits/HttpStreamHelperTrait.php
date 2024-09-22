<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Http\Traits;

use Pentagonal\Neon\WHMCS\Addon\Exceptions\InvalidArgumentDataTypeException;
use Pentagonal\Neon\WHMCS\Addon\Http\Factory\StreamFactory;
use Psr\Http\Message\StreamInterface;
use function is_object;
use function is_scalar;
use function method_exists;

trait HttpStreamHelperTrait
{
    /**
     * @param $body
     * @return StreamInterface
     */
    protected function determineBodyStream($body) : StreamInterface
    {
        if (is_scalar($body) || is_object($body) && method_exists($body, '__toString')) {
            $body = (new StreamFactory())->createStream((string) $body);
            $body->seek(0);
        } elseif (!$body instanceof StreamInterface) {
            throw new InvalidArgumentDataTypeException(
                'Invalid resource type: ' . gettype($body)
            );
        }
        return $body;
    }
}
