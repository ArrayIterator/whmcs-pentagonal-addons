<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Exceptions;

use Pentagonal\Neon\WHMCS\Addon\Interfaces\ThrowableInterface;
use RuntimeException;

class PermissionException extends RuntimeException implements ThrowableInterface
{
}
