<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Traits;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Schema\Abstracts\AbstractStructure;
use Swaggest\JsonSchema\InvalidValue;
use Throwable;
use function is_array;
use function is_bool;
use function is_subclass_of;
use function json_decode;
use function json_encode;
use function sprintf;

trait SingleSchemaTrait
{
    use SchemaTrait;

    /**
     * @var ?AbstractStructure $schema the schema
     */
    protected ?AbstractStructure $schema = null;

    /**
     * @var array $schemaArray the schema array
     */
    protected array $schemaArray;

    /**
     * @var bool $valid is schema valid
     */
    protected ?bool $valid = null;

    /**
     * @var bool $schemaInit is schema initialized
     */
    private bool $schemaInit = false;

    /**
     * @return bool is valid
     */
    public function isValid(): bool
    {
        if (is_bool($this->valid??null)) {
            return $this->valid;
        }
        $className = $this->getSchemaClassName();
        if (!$className || !is_subclass_of($className, AbstractStructure::class, true)) {
            return false;
        }
        $schema = $this->getSchema();
        return $this->valid ??= $schema instanceof $className;
    }

    /**
     * @return class-string<AbstractStructure>
     */
    abstract public function getSchemaClassName(): string;

    /**
     * Get Schema File
     */
    abstract public function getSchemaFile(): string;

    /**
     * Get the attribute value from name
     *
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->getSchemaArray()[$name]??null;
    }

    /**
     * Get schema from file
     */
    public function getSchema() : ?AbstractStructure
    {
        if ($this->schemaInit) {
            if (!$this->schema) {
                return null;
            }
            if (is_bool($this->valid??null)) {
                return $this->schema;
            }
            $className = $this->getSchemaClassName();
            if (!$className || !is_subclass_of($className, AbstractStructure::class, true)) {
                return null;
            }
            return $this->schema instanceof $className ? $this->schema : null;
        }
        $this->schemaInit = true;
        $this->schema = null;
        $this->valid = false;
        $refSchema = $this->getRefSchema();
        if (!$refSchema) {
            return null;
        }
        $className = $this->getSchemaClassName();
        if (!is_subclass_of($className, AbstractStructure::class)) {
            $this->error = new InvalidValue(sprintf('Invalid Schema Class: %s', $className));
            return null;
        }
        $performance = Performance::profile('validate_schema', static::class);
        try {
            $schemaObject = $this->readJson($this->getSchemaFile());
            $schema = $className::import($schemaObject);
            $this->valid = $schema instanceof AbstractStructure;
            $this->schema = null;
            if (!$this->valid) {
                $this->error = new InvalidValue('Invalid Schema');
                return null;
            }
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $refSchema->in(json_decode(json_encode($schema)));
            $this->schema = $schema;
        } catch (Throwable $e) {
            $this->error = $e;
        } finally {
            $performance->stop();
        }
        return $this->schema;
    }

    /**
     * @return Throwable|null
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * @inheritDoc
     */
    public function getSchemaArray() : array
    {
        if (is_array($this->schemaArray??null)) {
            return $this->schemaArray;
        }
        $schema = $this->getSchema();
        $this->schemaArray = $schema ? json_decode(json_encode($schema), true) : [];
        return $this->schemaArray;
    }

    /**
     * @inheritDoc
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function jsonSerialize()
    {
        $schema = $this->getSchema();
        return $schema ? $schema->jsonSerialize() : null;
    }
}
