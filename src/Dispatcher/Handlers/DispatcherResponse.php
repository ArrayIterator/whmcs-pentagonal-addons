<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Dispatcher\Handlers;

use Pentagonal\Neon\WHMCS\Addon\Dispatcher\Interfaces\DispatcherResponseInterface;
use Throwable;

/**
 * The dispatcher response
 */
class DispatcherResponse implements DispatcherResponseInterface
{
    /**
     * @var int $statusCode the status code
     */
    protected int $statusCode;

    /**
     * @var mixed|null $data the data
     */
    protected $data;

    /**
     * @var Throwable|null $error the error
     */
    protected ?Throwable $error;

    /**
     * Create new instance
     *
     * @param int|null $statusCode
     * @param $data
     * @param Throwable|null $error
     */
    public function __construct(
        int $statusCode = null,
        $data = null,
        ?Throwable $error = null
    ) {
        $this->data = $data;
        $this->error = $error;
        $this->statusCode = $statusCode??($error ? 500 : 200);
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set http status code
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
     *
     * @param int $statusCode
     * @return bool
     */
    public function setStatusCode(int $statusCode) : bool
    {
        if ($statusCode < 100 || $statusCode > 599) {
            return false;
        }
        $this->statusCode = $statusCode;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set data
     *
     * @param $data
     * @return void
     */
    public function setData($data) : void
    {
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * Set error
     *
     * @param Throwable|null $error
     * @return void
     */
    public function setError(?Throwable $error) : void
    {
        $this->error = $error;
    }
}
