<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Traits;

use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;
use Swaggest\JsonSchema\Structure\ObjectItemContract;
use Throwable;
use function file_get_contents;
use function is_object;
use function json_decode;
use function set_error_handler;
use const E_USER_WARNING;

trait SchemaTrait
{
    /**
     * @var ?ObjectItemContract $schema the schema
     */
    protected ?ObjectItemContract $schema = null;

    /**
     * @var ?SchemaContract $refSchema the reference schema
     */
    protected ?SchemaContract $refSchema = null;

    /**
     * @var bool $valid is schema valid
     */
    protected ?bool $valid = null;

    /**
     * @var bool $refSchemaInit is reference schema initialized
     */
    private bool $refSchemaInit = false;

    /**
     * @var bool $schemaInit is schema initialized
     */
    private bool $schemaInit = false;

    /**
     * @var ?Throwable $error the error
     */
    private ?Throwable $error = null;

    /**
     * @inheritdoc
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    abstract public function getRefSchemaFile(): string;

    /**
     * @inheritdoc
     */
    abstract public function getSchemaFile(): string;

    /**
     * @InheritDoc
     */
    public function get(string $name)
    {
        return $this->getSchemaArray()[$name]??null;
    }

    /**
     * @throws Throwable
     */
    private function createSchemaFromFile(string $file): ?SchemaContract
    {
        return Schema::import($this->readJson($file));
    }

    /**
     * @param string $file
     * @return ?array
     * @throws InvalidValue
     */
    private function readJson(string $file) : ?object
    {
        set_error_handler(
            static function ($errno, $errStr) {
                throw new InvalidValue($errStr, $errno);
            },
            E_USER_WARNING
        );
        try {
            $content = file_get_contents($file);
            if ($content === false) {
                throw new InvalidValue(sprintf('Failed to read file: %s', $file), E_USER_WARNING);
            }
        } finally {
            restore_error_handler();
        }

        $json = json_decode($content, false);
        if (!is_object($json)) {
            throw new InvalidValue('Invalid JSON Schema', E_USER_WARNING);
        }
        return $json;
    }

    /**
     * @return bool is valid
     */
    public function isValid(): bool
    {
        return $this->valid ??= $this->getSchema() instanceof ObjectItemContract;
    }

    /**
     * @inheritDoc
     */
    public function getRefSchema(): ?SchemaContract
    {
        if ($this->refSchemaInit) {
            return $this->refSchema;
        }
        $this->refSchemaInit = true;
        $this->refSchema = null;
        try {
            $this->refSchema = $this->createSchemaFromFile($this->getRefSchemaFile());
        } catch (Throwable $e) {
            $this->error = $e;
        }
        return $this->refSchema??null;
    }

    /**
     * @inheritDoc
     */
    public function getSchema() : ?ObjectItemContract
    {
        if ($this->schemaInit) {
            return $this->schema instanceof ObjectItemContract ? $this->schema : null;
        }
        $this->schemaInit = true;
        $this->schema = null;
        $this->valid = false;
        $refSchema = $this->getRefSchema();
        if (!$refSchema) {
            return null;
        }
        try {
            $schemaObject = $this->readJson($this->getSchemaFile());
            $this->schema = $refSchema->in($schemaObject);
            $this->valid = $this->schema instanceof ObjectItemContract;
            if (!$this->valid) {
                $this->error = new InvalidValue('Invalid Schema');
                $this->schema = null;
            }
        } catch (Throwable $e) {
            $this->error = $e;
        }
        return $this->schema;
    }

    /**
     * @inheritDoc
     */
    public function getSchemaArray() : array
    {
        $schema = $this->getSchema();
        return $schema ? $schema->toArray() : [];
    }
}
