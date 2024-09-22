<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Exceptions;

use Pentagonal\Neon\WHMCS\Addon\Interfaces\ThrowableInterface;
use UnexpectedValueException as CoreUnexpectedValueException;

class UnexpectedValueException extends CoreUnexpectedValueException implements ThrowableInterface
{
}
