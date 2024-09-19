<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces;

interface TagValueSupportInterface extends TagInterface
{
    /**
     * Get tag value
     *
     * @return mixed
     */
    public function getValue();
}
