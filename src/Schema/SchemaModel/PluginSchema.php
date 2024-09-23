<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\SchemaModel;

use Pentagonal\Hub\Schema;
use Pentagonal\Hub\Schema\Whmcs\Plugin;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\InvalidArgumentCriteriaException;
use Pentagonal\Neon\WHMCS\Addon\Exceptions\UnsupportedArgumentDataTypeException;
use Pentagonal\Neon\WHMCS\Addon\Helpers\DataNormalizer;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\PluginInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\PluginSchemaInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Traits\SchemaTrait;
use ReflectionObject;
use Swaggest\JsonSchema\Structure\ObjectItemContract;
use function array_keys;
use function dirname;
use function file_exists;
use function get_class;

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
        return $this->refSchemaFile ??= Schema::determineInternalSchemaURL(Plugin::class);
    }

    /**
     * @inheritDoc
     */
    public function getRefSchema(): ?ObjectItemContract
    {
        return $this->refSchema ??= $this->createSchemaByReferenceClassName(Plugin::class);
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
     * @throws InvalidArgumentCriteriaException
     */
    public function getSchemaPlugin(string $pluginDirectory) : Plugin
    {
        $schemaFile = DataNormalizer::makeUnixSeparator($pluginDirectory . '/plugin.json');
        if (!file_exists($schemaFile)) {
            throw new InvalidArgumentCriteriaException(
                'Schema file for plugin does not exists'
            );
        }
        if (isset(self::$schemaLists[$schemaFile])) {
            return clone self::$schemaLists[$schemaFile];
        }
        self::$schemaLists[$schemaFile] = $this->createSchemaStructureFor($schemaFile, Plugin::class);
        return clone self::$schemaLists[$schemaFile];
    }

    /**
     * @throws UnsupportedArgumentDataTypeException|InvalidArgumentCriteriaException
     */
    public function getSchema(PluginInterface $plugin): Plugin
    {
        $className = get_class($plugin);
        if (isset(self::$pluginJsonPath[$className])) {
            return $this->getSchemaPlugin(dirname(self::$pluginJsonPath[$className]));
        }
        $ref = new ReflectionObject($plugin);
        if ($ref->isAnonymous()) {
            throw new UnsupportedArgumentDataTypeException('Schema does not accept anonymous class');
        }
        $path = $ref->getFileName();
        if (!$path) {
            throw new InvalidArgumentCriteriaException(
                sprintf('Plugin object: %s does not have path', $className)
            );
        }
        $path = DataNormalizer::makeUnixSeparator(dirname($path) . '/plugin.json');
        self::$pluginJsonPath[$className] = $path;
        if (isset(self::$schemaLists[$path])) {
            return clone self::$schemaLists[$path];
        }
        return $this->getSchemaPlugin($path);
    }
}
