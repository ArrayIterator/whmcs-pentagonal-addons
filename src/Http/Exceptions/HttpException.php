<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Http\Exceptions;

use Pentagonal\Neon\WHMCS\Addon\Http\Interfaces\HttpExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

class HttpException extends RuntimeException implements HttpExceptionInterface
{
    protected ServerRequestInterface $request;

    protected ?string $title = null;

    protected string $description = '';

    public function __construct(
        ServerRequestInterface $request,
        string $message = '',
        int $code = 0,
        Throwable $previousException = null
    ) {
        parent::__construct($message, $code, $previousException);
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getTitle(): string
    {
        return $this->title??'';
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
