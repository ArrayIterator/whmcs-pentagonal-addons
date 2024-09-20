<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Abstracts;

use Pentagonal\Neon\WHMCS\Addon\Interfaces\HooksInterface;

/**
 * Abstract Hook
 */
abstract class AbstractHook extends AbstractBaseHook
{
    /**
     * @inheritDoc
     * @final
     */
    final public function __construct(HooksInterface $hooks)
    {
        parent::__construct($hooks);
    }
}
