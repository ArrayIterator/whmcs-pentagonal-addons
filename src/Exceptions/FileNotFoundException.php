<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Exceptions;

use InvalidArgumentException;
use Throwable;
use function sprintf;

class FileNotFoundException extends InvalidArgumentException
{
    protected string $fileName;
    public function __construct(
        string $fileName,
        string $message = "",
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->fileName = $fileName;
        if (!$message) {
            $message = sprintf('File %s has not found', $this->fileName);
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getFileName() : string
    {
        return $this->fileName;
    }
}
