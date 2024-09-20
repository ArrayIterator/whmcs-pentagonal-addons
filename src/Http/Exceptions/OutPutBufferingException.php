<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Http\Exceptions;

use Pentagonal\Neon\WHMCS\Addon\Http\Factory\StreamFactory;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

class OutPutBufferingException extends RuntimeException
{
    protected StreamInterface $stream;

    public function __construct(
        ?StreamInterface $stream = null,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->stream = $stream??(new StreamFactory())->createStream(
            ob_get_contents()?:''
        );
        parent::__construct($message, $code, $previous);
    }

    public function getStream(): StreamInterface
    {
        return $this->stream;
    }
}
