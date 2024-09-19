<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema;

use Pentagonal\Neon\WHMCS\Addon\Core;
use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\JsonSchemaInterface;
use function get_class;
use function is_string;

/**
 * @template T of JsonSchemaInterface
 */
class Schemas
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
        StructureSchema::class => true
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
        foreach ($this->keep as $className => $value) {
            $this->schemas[$className] = new $className($this->getCore()->getTheme());
        }
    }

    /**
     * @return Core
     */
    public function getCore(): Core
    {
        return $this->core;
    }

    /**
     * Check if schema is removable
     *
     * @param $schemaClassName
     * @return bool
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
     * Add schema
     *
     * @param T $schema
     * @return void
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
     * Remove schema
     *
     * @param string $schemaClassName
     * @return void
     */
    public function remove(string $schemaClassName) : void
    {
        if (!$this->isRemovable($schemaClassName)) {
            return;
        }
        unset($this->schemas[$schemaClassName]);
    }

    /**
     * Get schema
     *
     * @param class-string<T> $schemaClassName
     * @return ?T
     */
    public function get(string $schemaClassName) : ?JsonSchemaInterface
    {
        return $this->schemas[$schemaClassName] ?? null;
    }
}
