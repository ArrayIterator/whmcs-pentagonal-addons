<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Abstracts;

use Pentagonal\Neon\WHMCS\Addon\Interfaces\HooksServiceInterface;

/**
 * Abstract Hook
 */
abstract class AbstractHook extends AbstractBaseHook
{
    /**
     * @inheritDoc
     * @final
     */
    final public function __construct(HooksServiceInterface $hooks)
    {
        parent::__construct($hooks);
    }
}
