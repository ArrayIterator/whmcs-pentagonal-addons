<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Http;

use Pentagonal\Neon\WHMCS\Addon\Exceptions\InvalidArgumentCriteriaException;
use Pentagonal\Neon\WHMCS\Addon\Http\Traits\HttpStreamHelperTrait;
use Psr\Http\Message\ResponseInterface;
use function is_int;

class Response extends Message implements ResponseInterface
{
    use HttpStreamHelperTrait;

    /**
     * @var int
     */
    private int $statusCode;

    /**
     * @var string
     */
    private string $reasonPhrase;

    /**
     * @param int $status  Status code
     * @param array<string, string|string[]> $headers Response headers
     * @param string|resource|\Psr\Http\Message\StreamInterface|null $body Response body
     * @param string $version Protocol version
     * @param ?string $reason  Reason phrase (when empty a default will be used based on the status code)
     *
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        string $reason = null
    ) {
        $this->assertStatusCodeRange($status);
        if ($body) {
            $this->stream = $this->determineBodyStream($body);
        }

        $this->statusCode = $status;
        $this->setHeaders($headers);
        if ($reason === '' && isset(Code::REASON_PHRASES[$this->statusCode])) {
            $this->reasonPhrase = Code::REASON_PHRASES[$this->statusCode];
        } else {
            $this->reasonPhrase = (string) $reason;
        }
        $this->protocolVersion = $version;
    }

    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = '') : ResponseInterface
    {
        $this->assertStatusCodeIsInteger($code);
        $code = (int) $code;
        $this->assertStatusCodeRange($code);

        $obj = clone $this;
        $obj->statusCode = $code;
        if ($reasonPhrase === '' && isset(Code::REASON_PHRASES[$obj->statusCode])) {
            $reasonPhrase = Code::REASON_PHRASES[$obj->statusCode];
        }
        $obj->reasonPhrase = (string) $reasonPhrase;
        return $obj;
    }

    public function getReasonPhrase() : string
    {
        return $this->reasonPhrase;
    }

    /**
     * @param mixed $statusCode
     */
    private function assertStatusCodeIsInteger($statusCode) : void
    {
        if (!is_int($statusCode)) {
            throw new InvalidArgumentCriteriaException(
                'Status code must be an integer value.'
            );
        }
    }

    private function assertStatusCodeRange(int $statusCode) : void
    {
        if ($statusCode < 100 || $statusCode >= 600) {
            throw new InvalidArgumentCriteriaException(
                'Status code must be an integer value between 1xx and 5xx.'
            );
        }
    }
}
