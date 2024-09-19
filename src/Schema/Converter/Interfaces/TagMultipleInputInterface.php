<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces;

interface TagMultipleInputInterface extends TagMultipleSupportInterface, TagInputInterface
{
    /**
     * @return array<TagInputInterface>
     */
    public function getInputs() : array;

    /**
     * @return array<TagInputInterface>
     */
    public function getSelected() : array;
}
