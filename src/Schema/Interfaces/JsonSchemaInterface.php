<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces;

use JsonSerializable;
use Swaggest\JsonSchema\Structure\ObjectItemContract;
use Throwable;

interface JsonSchemaInterface extends JsonSerializable
{
    /**
     * JsonSchemaInterface constructor.
     *
     * @param SchemasInterface $schemas
     */
    public function __construct(SchemasInterface $schemas);

    /**
     * Get schemas
     *
     * @return SchemasInterface
     */
    public function getSchemas(): SchemasInterface;

    /**
     * Error message
     *
     * @return ?Throwable
     */
    public function getError() : ?Throwable;

    /**
     * Get the schema reference file
     *
     * @return string the schema reference file
     */
    public function getRefSchemaFile() : string;

    /**
     * Get ref schema - the reference of schema
     *
     * @return ?ObjectItemContract null if failed
     */
    public function getRefSchema(): ?ObjectItemContract;
}
