<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema;

use Pentagonal\Neon\WHMCS\Addon\Core;
use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\JsonSchemaInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\SchemasInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\SchemaModel\PluginSchema;
use Pentagonal\Neon\WHMCS\Addon\Schema\SchemaModel\ThemeSchema;
use function get_class;
use function is_string;

/**
 * @template T of JsonSchemaInterface
 */
class Schemas implements SchemasInterface
{
    /**
     * @var Core $core the core
     */
    private Core $core;

    /**
     * @var array<class-string<T>, T> $schemas the schemas
     */
    private array $schemas = [];

    /**
     * @var array<class-string<T>, true> $keep the keep
     */
    private array $keep = [
        ThemeSchema::class => true,
        PluginSchema::class => true,
    ];

    /**
     * Schemas constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        $this->core = $core;
        $theme = $this->getCore()->getTheme();
        if (!$theme) {
            $this->keep = [];
            return;
        }
        /**
         * @var class-string<T> $className
         */
        foreach ($this->keep as $className => $value) {
            $this->schemas[$className] = new $className($this);
        }
    }

    /**
     * @inheritDoc
     */
    public function getCore(): Core
    {
        return $this->core;
    }

    /**
     * @inheritDoc
     */
    public function isRemovable($schemaClassName): bool
    {
        if ($schemaClassName instanceof JsonSchemaInterface) {
            $schemaClassName = get_class($schemaClassName);
        } elseif (!is_string($schemaClassName)) {
            return false;
        }
        return !isset($this->keep[$schemaClassName]);
    }

    /**
     * @inheritDoc
     */
    public function add(JsonSchemaInterface $schema) : void
    {
        $className = get_class($schema);
        if (is_string($this->schemas[$className]) && !$this->isRemovable($className)) {
            return;
        }
        $this->schemas[get_class($schema)] = $schema;
    }

    /**
     * @inheritDoc
     */
    public function remove(string $schemaClassName) : void
    {
        if (!$this->isRemovable($schemaClassName)) {
            return;
        }
        unset($this->schemas[$schemaClassName]);
    }

    /**
     * @inheritDoc
     */
    public function get(string $schemaClassName) : ?JsonSchemaInterface
    {
        return $this->schemas[$schemaClassName] ?? null;
    }
}
