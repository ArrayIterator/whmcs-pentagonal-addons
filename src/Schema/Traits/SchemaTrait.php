<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Traits;

use Pentagonal\Neon\WHMCS\Addon\Exceptions\InvalidArgumentCriteriaException;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\InvalidDataTypeException;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\UnexpectedValueException;
use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Schema\Abstracts\AbstractStructure;
use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\SchemasInterface;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;
use Swaggest\JsonSchema\Structure\ObjectItemContract;
use Throwable;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function is_dir;
use function is_file;
use function is_object;
use function is_subclass_of;
use function is_writable;
use function json_decode;
use function mkdir;
use function pathinfo;
use function preg_match;
use function realpath;
use function restore_error_handler;
use function set_error_handler;
use function sha1;
use function sprintf;
use function sys_get_temp_dir;
use function time;
use function unlink;
use const E_USER_WARNING;
use const PATHINFO_EXTENSION;

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
                    return $className::import($this->readJson($file));
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
     * @param string $file
     * @return ?array
     * @throws UnexpectedValueException
     */
    protected function readJson(string $file) : ?object
    {
        $performance = Performance::profile('read_json', 'system.schema')
            ->setData([
                'file' => $file,
            ]);
        $json = null;
        try {
            set_error_handler(
                static function ($errno, $errStr) {
                    throw new InvalidDataTypeException($errStr, $errno);
                },
                E_USER_WARNING
            );
            $originalFile = $file;
            $isRemote = preg_match('/^https?:\/\//', $file);
            // cache directory
            $tempDir = sys_get_temp_dir() . '/schemas';
            $remoteFile = $tempDir . '/' . sha1($file) . '.' . pathinfo($file, PATHINFO_EXTENSION);
            $isCached = false;
            if ($isRemote) {
                if (!is_dir($tempDir)) {
                    set_error_handler(function () {
                    });
                    mkdir($tempDir, 0777, true);
                    restore_error_handler();
                }
                if (is_file($remoteFile) && filemtime($remoteFile) > (time() - 3600)) {
                    $isCached = true;
                    $file = $remoteFile;
                }
            }
            $readPerformance = Performance::profile('read_json_file', 'system.schema')
                ->setData([
                    'file' => $file,
                    'original_file' => $originalFile,
                    'is_cached' => $isCached,
                    'is_remote' => $isRemote,
                ]);
            try {
                $content = file_get_contents($file);
                if (!$isCached && $isRemote && is_dir($tempDir) && is_writable($tempDir)) {
                    if (is_file($remoteFile)) {
                        unlink($remoteFile);
                    }
                    file_put_contents($remoteFile, $content === false ? 'false' : $content);
                }
                if ($content === false || $content === 'false') {
                    throw new UnexpectedValueException(sprintf('Failed to read file: %s', $originalFile), E_USER_WARNING);
                }
            } finally {
                restore_error_handler();
                $readPerformance->stop();
            }

            $json = json_decode($content, false);
            if (!is_object($json)) {
                throw new UnexpectedValueException(sprintf('Invalid JSON Schema: %s', $content), E_USER_WARNING);
            }
        } finally {
            $performance->stop();
        }
        return $json;
    }

    /**
     * Create schema
     *
     * @template T of AbstractStructure
     * Get schema from file
     * @param string $file the file of json
     * @param class-string<T> $className the classname structure
     * @throws InvalidArgumentCriteriaException|UnexpectedValueException
     * @return T
     */
    public function createSchemaStructureFor(string $file, string $className) : ?AbstractStructure
    {
        if (!file_exists($file)) {
            return null;
        }
        $file = realpath($file)?:$file;
        $refSchema = $this->getRefSchema();
        if (!$refSchema) {
            return null;
        }
        if (!is_subclass_of($className, AbstractStructure::class)) {
            throw new InvalidArgumentCriteriaException(sprintf('Invalid Schema Class: %s', $className));
        }
        $performance = Performance::profile('validate_schema', 'system.schema')
            ->setData([
                'file' => $file,
                'class' => $className
            ]);
        try {
            $schema = $this->createSchemaFromFile($file, $className);
            if (!$schema instanceof AbstractStructure || ! $schema instanceof $className) {
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
