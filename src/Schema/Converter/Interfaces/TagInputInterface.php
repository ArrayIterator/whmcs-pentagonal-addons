<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces;

interface TagInputInterface extends TagValueSupportInterface
{
    /**
     * Get the name
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Get the type
     *
     * @return string
     */
    public function getType() : string;

    /**
     * Check if the value has a default value
     *
     * @return bool
     */
    public function hasDefaultValue(): bool;

    /**
     * Get the default value
     *
     * @return mixed
     */
    public function getDefaultValue();

    /**
     * Check if the input is required
     *
     * @return bool
     */
    public function isRequired(): bool;

    /**
     * Check if the input is disabled
     *
     * @return bool
     */
    public function isDisabled(): bool;

    /**
     * Check if the input is readonly
     *
     * @return bool
     */
    public function isReadonly(): bool;

    /**
     * Check if the input is multiple
     *
     * @return bool
     */
    public function isMultiple(): bool;
}
