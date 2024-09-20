<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Http;

use Pentagonal\Neon\WHMCS\Addon\Http\Factory\RequestFactory;
use Pentagonal\Neon\WHMCS\Addon\Http\Factory\ResponseFactory;
use Pentagonal\Neon\WHMCS\Addon\Http\Factory\ServerRequestFactory;
use Pentagonal\Neon\WHMCS\Addon\Http\Factory\StreamFactory;
use Pentagonal\Neon\WHMCS\Addon\Http\Factory\UploadedFileFactory;
use Pentagonal\Neon\WHMCS\Addon\Http\Factory\UriFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpFactory
{
    protected RequestFactoryInterface $requestFactory;

    protected ResponseFactoryInterface $responseFactory;

    protected ServerRequestFactoryInterface  $serverRequestFactory;

    protected StreamFactoryInterface $streamFactory;

    protected UploadedFileFactoryInterface $uploadedFileFactory;

    protected UriFactoryInterface $uriFactory;

    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory ??= new RequestFactory();
    }

    public function setRequestFactory(RequestFactoryInterface $requestFactory): void
    {
        $this->requestFactory = $requestFactory;
    }

    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory ??= new ResponseFactory();
    }

    public function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        $this->responseFactory = $responseFactory;
    }

    public function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        return $this->serverRequestFactory ??= new ServerRequestFactory();
    }

    public function setServerRequestFactory(ServerRequestFactoryInterface $serverRequestFactory): void
    {
        $this->serverRequestFactory = $serverRequestFactory;
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory ??= new StreamFactory();
    }

    public function setStreamFactory(StreamFactoryInterface $streamFactory): void
    {
        $this->streamFactory = $streamFactory;
    }

    public function getUploadedFileFactory(): UploadedFileFactoryInterface
    {
        return $this->uploadedFileFactory ??= new UploadedFileFactory();
    }

    public function setUploadedFileFactory(UploadedFileFactoryInterface $uploadedFileFactory): void
    {
        $this->uploadedFileFactory = $uploadedFileFactory;
    }

    public function getUriFactory(): UriFactoryInterface
    {
        return $this->uriFactory ??= new UriFactory();
    }

    public function setUriFactory(UriFactoryInterface $uriFactory): void
    {
        $this->uriFactory = $uriFactory;
    }
}
