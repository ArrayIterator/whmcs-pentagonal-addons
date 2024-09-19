<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces;

interface OptionSchemaInterface extends JsonSchemaInterface
{
    public const REF = "https://hub.pentagonal.org/schemas/whmcs/addons/pentagonal/options.json";

    /**
     * Get the schema source keyof : $schema
     *
     * @return string
     */
    public function getSchemaSource() : string;
}
