<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Abstracts;

use Swaggest\JsonSchema\Structure\ClassStructure;
use Swaggest\JsonSchema\Structure\ObjectItemContract;

abstract class AbstractStructure extends ClassStructure implements ObjectItemContract
{
    /**
     * The schema - draft 7
     * @var string SCHEMA The schema uri of the schema
     */
    public const SCHEMA = 'http://json-schema.org/draft-07/schema';

    /**
     * @var string $schema The schema uri of the schema
     */
    public string $schema = self::SCHEMA;

    /**
     * @var string $version The version of the schema
     */
    public string $version = '';

    /**
     * Get the schema uri of the schema
     *
     * @return string The schema uri of the schema
     */
    public function getSchema(): string
    {
        return $this->schema;
    }

    /**
     * Get the version of the schema
     *
     * @return string the version of the schema
     */
    public function getVersion(): string
    {
        return $this->version;
    }
}
