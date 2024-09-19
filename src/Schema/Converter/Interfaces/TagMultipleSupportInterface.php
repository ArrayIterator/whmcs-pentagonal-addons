<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces;

interface TagMultipleSupportInterface extends TagInterface
{
    /**
     * Get selected value
     *
     * @return array
     */
    public function getSelected(): array;
}
