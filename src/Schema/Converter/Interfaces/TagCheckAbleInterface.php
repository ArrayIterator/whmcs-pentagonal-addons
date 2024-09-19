<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces;

interface TagCheckAbleInterface extends TagInputInterface
{
    /**
     * Check if the input is checked
     *
     * @return bool
     */
    public function isChecked() : bool;
}
