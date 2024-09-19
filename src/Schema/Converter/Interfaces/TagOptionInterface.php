<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces;

interface TagOptionInterface extends TagValueSupportInterface
{
    /**
     * Get the label
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Check if the option is disabled
     *
     * @return bool
     */
    public function isDisabled(): bool;

    /**
     * Check if the option is selected
     *
     * @return bool
     */
    public function isSelected(): bool;
}
