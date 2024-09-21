<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\SchemaModel;

use Pentagonal\Neon\WHMCS\Addon\Helpers\DataNormalizer;
use RuntimeException;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\PluginInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\PluginSchemaInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Plugin;
use Pentagonal\Neon\WHMCS\Addon\Schema\Traits\SchemaTrait;
use ReflectionObject;
use Swaggest\JsonSchema\Structure\ObjectItemContract;
use function array_keys;
use function dirname;
use function file_exists;
use function get_class;
use function realpath;
use const DIRECTORY_SEPARATOR;

class PluginSchema implements PluginSchemaInterface
{
    use SchemaTrait {
        __construct as private construct;
    }

    /**
     * @var array<string, Plugin>
     */
    private static array $schemaLists = [];

    /**
     * @var array<class-string<PluginInterface>, string>
     */
    protected static array $pluginJsonPath = [];

    /**
     * @inheritDoc
     */

    public function getRefSchemaFile(): string
    {
        $file = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'schema' . DIRECTORY_SEPARATOR . 'plugin.json';
        return $this->refSchemaFile ??= file_exists($file)
            ? (realpath($file)?:$file)
            :  Plugin::ID;
    }

    /**
     * @inheritDoc
     */
    public function getRefSchema(): ?ObjectItemContract
    {
        return $this->refSchema ??= Plugin::schema()->exportSchema();
    }

    /**
     * @inheritDoc
     */

    public function jsonSerialize()
    {
        // nothing
        return array_keys(self::$schemaLists);
    }

    /**
     * GHet schema from plugin directory
     *
     * @param string $pluginDirectory
     * @return Plugin
     * @throws \Throwable
     */
    public function getSchemaPlugin(string $pluginDirectory) : Plugin
    {
        $schemaFile = DataNormalizer::makeUnixSeparator($pluginDirectory .'/plugin.json');
        if (isset(self::$schemaLists[$schemaFile])) {
            return clone self::$schemaLists[$schemaFile];
        }
        if (!file_exists($schemaFile)) {
            throw new RuntimeException(
                'Schema file plugin.json does not exists'
            );
        }
        self::$schemaLists[$schemaFile] = $this->createSchemaStructureFor($schemaFile, Plugin::class);
        return clone self::$schemaLists[$schemaFile];
    }

    /**
     * @throws \Throwable
     */
    public function getSchema(PluginInterface $plugin): Plugin
    {
        $className = get_class($plugin);
        if (isset(self::$pluginJsonPath[$className])) {
            return $this->getSchemaPlugin(dirname(self::$pluginJsonPath[$className]));
        }
        $ref = new ReflectionObject($plugin);
        if ($ref->isAnonymous()) {
            throw new RuntimeException('Schema does not accept anonymous class');
        }
        $path = $ref->getFileName();
        if (!$path) {
            throw new RuntimeException(
                sprintf('Plugin object: %s does not have path', $className)
            );
        }
        $schemaFile = DataNormalizer::makeUnixSeparator($path .'/plugin.json');
        self::$pluginJsonPath[$className] = $schemaFile;
        if (isset(self::$schemaLists[$schemaFile])) {
            return clone self::$schemaLists[$schemaFile];
        }
        return $this->getSchemaPlugin($path);
    }
}
