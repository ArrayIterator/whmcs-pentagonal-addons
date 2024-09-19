<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces;

use Swaggest\JsonSchema\SchemaContract;
use Swaggest\JsonSchema\Structure\ObjectItemContract;
use Throwable;

interface JsonSchemaInterface
{
    /**
     * Error message
     *
     * @return ?Throwable
     */
    public function getError() : ?Throwable;

    /**
     * Check if the schema is valid
     *
     * @return bool
     */
    public function isValid() : bool;

    /**
     * Get the schema file
     *
     * @return string
     */
    public function getSchemaFile() : string;

    /**
     * Get the schema reference file
     *
     * @return string the schema reference file
     */
    public function getRefSchemaFile() : string;

    /**
     * Get the schema
     *
     * @return array null if failed
     */
    public function getSchemaArray() : array;

    /**
     * Get schema object item
     *
     * @return ?ObjectItemContract
     */
    public function getSchema() : ?ObjectItemContract;

    /**
     * Get ref schema - the reference of schema
     *
     * @return ?SchemaContract null if failed
     */
    public function getRefSchema(): ?SchemaContract;

    /**
     * Get the value by name
     *
     * @param string $name
     * @return mixed
     */
    public function get(string $name);
}
