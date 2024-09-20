<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Traits;

use Pentagonal\Neon\WHMCS\Addon\Helpers\Performance;
use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\SchemasInterface;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;
use Swaggest\JsonSchema\Structure\ObjectItemContract;
use Throwable;
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
     * Create schema from file
     *
     * @param string $file
     * @param string $className
     * @return SchemaContract|null
     * @throws InvalidValue
     * @throws Throwable
     */
    public function createSchemaFromFile(string $file, string $className = Schema::class): ?SchemaContract
    {
        $performance = Performance::profile('create_schema_from_file', static::class)
            ->setData([
                'file' => $file,
                'class' => $className,
            ]);
        try {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            if ($className === Schema::class || is_subclass_of($className, Schema::class, true)) {
                return $className::import($this->readJson($file));
            }
            throw new InvalidValue(sprintf('Invalid Schema Class: %s', $className), E_USER_WARNING);
        } finally {
            $performance->stop();
        }
    }

    /**
     * @param string $file
     * @return ?array
     * @throws InvalidValue
     */
    protected function readJson(string $file) : ?object
    {
        $performance = Performance::profile('read_json', static::class)
            ->setData([
                'file' => $file,
            ]);
        $json = null;
        try {
            set_error_handler(
                static function ($errno, $errStr) {
                    throw new InvalidValue($errStr, $errno);
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
            $readPerformance = Performance::profile('read_json_file', static::class)
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
                    throw new InvalidValue(sprintf('Failed to read file: %s', $originalFile), E_USER_WARNING);
                }
            } finally {
                restore_error_handler();
                $readPerformance->stop();
            }

            $json = json_decode($content, false);
            if (!is_object($json)) {
                throw new InvalidValue('Invalid JSON Schema', E_USER_WARNING);
            }
        } finally {
            $performance->stop();
        }
        return $json;
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
