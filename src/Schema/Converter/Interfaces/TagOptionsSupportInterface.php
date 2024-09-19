<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces;

interface TagOptionsSupportInterface extends TagInterface
{
    /**
     * @return TagOptionsInterface
     */
    public function getOptions(): TagOptionsInterface;

    /**
     * Get selected value
     *
     * @return mixed
     */
    public function getSelected();
}
