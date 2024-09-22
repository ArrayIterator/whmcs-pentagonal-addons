<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Exceptions;

use InvalidArgumentException;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\ThrowableInterface;

class UnsupportedArgumentDataTypeException extends InvalidArgumentException implements ThrowableInterface
{
}
