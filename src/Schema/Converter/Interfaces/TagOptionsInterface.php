<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces;

interface TagOptionsInterface extends TagInterface
{
    /**
     * @return array<TagOptionInterface>
     */
    public function getOptions(): array;

    /**
     * Get selected value
     *
     * @return mixed
     */
    public function getSelected();
}
