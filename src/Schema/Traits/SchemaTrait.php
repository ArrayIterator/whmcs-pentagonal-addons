<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Traits;

use Pentagonal\Hub\Abstracts\AbstractSchemaStructure;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\InvalidArgumentCriteriaException;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\UnexpectedValueException;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\SchemasInterface;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;
use Swaggest\JsonSchema\Structure\ObjectItemContract;
use Throwable;
use function file_exists;
use function is_subclass_of;
use function realpath;
use function sprintf;
use const E_USER_WARNING;

trait SchemaTrait
{
    /**
     * @var ?ObjectItemContract $refSchema the reference schema
     */
    protected ?ObjectItemContract $refSchema = null;

    /**
     * @var bool $refSchemaInit is reference schema initialized
     */
    private bool $refSchemaInit = false;

    /**
     * @var ?Throwable $error the error
     */
    protected ?Throwable $error = null;

    /**
     * @var SchemasInterface $schemas the core
     */
    protected SchemasInterface $schemas;

    /**
     * Theme constructor.
     *
     * @param SchemasInterface $schemas
     */
    public function __construct(SchemasInterface $schemas)
    {
        $this->schemas = $schemas;
    }

    /**
     * @inheritDoc
     */
    public function getSchemas() : SchemasInterface
    {
        return $this->schemas;
    }

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
     * @template T of ObjectItemContract
     * Create schema from file
     *
     * @param string $file
     * @param class-string<T> $className
     * @return ?T
     * @throws UnexpectedValueException
     */
    public function createSchemaFromFile(string $file, string $className = Schema::class): ?ObjectItemContract
    {
        $performance = Performance::profile('create_schema_from_file', 'system.schema')
            ->setData([
                'file' => $file,
                'class' => $className,
            ]);
        try {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            if ($className === Schema::class || is_subclass_of($className, ClassStructure::class, true)) {
                try {
                    return \Pentagonal\Hub\Schema::createSchemaFromFile($file, $className);
                } catch (Throwable $e) {
                    throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
                }
            }
            throw new UnexpectedValueException(sprintf('Invalid Schema Class: %s', $className), E_USER_WARNING);
        } finally {
            $performance->stop();
        }
    }

    /**
     * Create schema
     *
     * @template T of AbstractSchemaStructure
     * Get schema from file
     * @param string $file the file of json
     * @param class-string<T> $className the classname structure
     * @throws InvalidArgumentCriteriaException|UnexpectedValueException
     * @return T
     */
    public function createSchemaStructureFor(string $file, string $className) : ?AbstractSchemaStructure
    {
        if (!file_exists($file)) {
            return null;
        }
        $file = realpath($file)?:$file;
        $refSchema = $this->getRefSchema();
        if (!$refSchema) {
            return null;
        }
        if (!is_subclass_of($className, AbstractSchemaStructure::class)) {
            throw new InvalidArgumentCriteriaException(sprintf('Invalid Schema Class: %s', $className));
        }
        $performance = Performance::profile('validate_schema', 'system.schema')
            ->setData([
                'file' => $file,
                'class' => $className
            ]);
        try {
            $schema = $this->createSchemaFromFile($file, $className);
            if (!$schema instanceof AbstractSchemaStructure || ! $schema instanceof $className) {
                throw new InvalidArgumentCriteriaException('Invalid Schema');
            }
            $context = new Context();
            $context->skipValidation = true;
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $refSchema->in($schema->jsonSerialize(), $context);
            $this->schema = $schema;
        } finally {
            $performance->stop();
        }
        return $schema;
    }

    /**
     * @inheritDoc
     */
    public function getRefSchema(): ?ObjectItemContract
    {
        if ($this->refSchemaInit) {
            return $this->refSchema;
        }
        $this->refSchemaInit = true;
        $this->refSchema = null;
        try {
            $this->refSchema =  $this->createSchemaFromFile($this->getRefSchemaFile());
        } catch (Throwable $e) {
            $this->error = $e;
        }
        return $this->refSchema??null;
    }
}
