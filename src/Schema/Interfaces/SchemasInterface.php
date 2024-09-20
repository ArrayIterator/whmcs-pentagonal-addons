<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces;

use Pentagonal\Neon\WHMCS\Addon\Core;

/**
 * @template T of JsonSchemaInterface
 */
interface SchemasInterface
{
    /**
     * SchemasInterface constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core);

    /**
     * Get the core
     *
     * @return Core
     */
    public function getCore(): Core;

    /**
     * Check if schema is removable
     *
     * @param $schemaClassName
     * @return bool
     */
    public function isRemovable($schemaClassName): bool;
    /**
     * Add schema
     *
     * @param T $schema
     * @return void
     */
    public function add(JsonSchemaInterface $schema);

    /**
     * Remove schema
     *
     * @param string $schemaClassName
     * @return void
     */
    public function remove(string $schemaClassName);

    /**
     * Get schema
     *
     * @param class-string<T> $schemaClassName
     * @return ?T
     */
    public function get(string $schemaClassName) : ?JsonSchemaInterface;
}
