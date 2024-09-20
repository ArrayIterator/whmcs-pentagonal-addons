<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces;

use Swaggest\JsonSchema\Structure\ObjectItemContract;

interface SingleSchemaInterface extends JsonSchemaInterface
{
    /**
     * Get the schema file
     *
     * @return string
     */
    public function getSchemaFile() : string;

    /**
     * Check if the schema is valid
     *
     * @return bool
     */
    public function isValid() : bool;

    /**
     * Get the schema
     *
     * @return array null if failed
     */
    public function getSchemaArray() : array;

    /**
     * Get schema object item
     *
     * @return ?ObjectItemContract null if failed
     */
    public function getSchema() : ?ObjectItemContract;

    /**
     * Get the value by name
     *
     * @param string $name
     * @return mixed
     */
    public function get(string $name);
}
