<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures;

use Pentagonal\Neon\WHMCS\Addon\Schema\Abstracts\AbstractStructure;
use Swaggest\JsonSchema\Constraint\Format;
use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use function str_replace;

class Plugin extends AbstractStructure
{
    /**
     * @var string The schema uri of the plugin
     */
    public const ID = 'https://hub.pentagonal.org/schemas/plugin.json';

    /**
     * @var string The version of the plugin
     */
    public const VERSION = '1.0.0';

    /**
     * @var string The schema uri of the plugin
     */
    public string $schema = self::ID;

    /**
     * @var string The id of the plugin
     */
    public string $id = '';

    /**
     * @var string The name of the plugin
     */
    public string $name;

    /**
     * @var ?string The namespace of the plugin
     */
    public ?string $namespace = null;

    /**
     * @var string The version of the plugin
     */
    public string $url = '';

    /**
     * @var string The author of the plugin
     */
    public string $author = '';

    /**
     * @var string The author url of the plugin
     */
    public string $author_url = '';

    /**
     * @var string The license of the plugin
     */
    public string $license = '';

    /**
     * @var string The license url of the plugin
     */
    public string $license_url = '';

    /**
     * @var bool Enable Page on addon plugin
     */
    public bool $enable_admin_page = false;

    /**
     * @InheritDoc
     * @noinspection PhpUndefinedFieldInspection
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema->{Schema::PROP_ID} = self::ID;
        $ownerSchema->version = self::VERSION;
        $ownerSchema
            ->setSchema(self::SCHEMA)
            //->setId(self::ID) // bug
            ->setTitle('Schema for plugin')
            ->setDescription('The schema for plugin')
            ->setType(JsonSchema::OBJECT)
            ->setRequired([
                'name',
                'namespace',
            ])
            ->addPropertyMapping('$schema', 'schema')
            ->addPropertyMapping(Schema::PROP_ID, 'id');

        $properties->schema = Schema::string()
            ->setDescription('The schema uri of the plugin')
            ->setFormat(Format::URI_REFERENCE);
        $properties->id = Schema::string()
            ->setDescription('The id of the plugin')
            ->setFormat(Format::URI_REFERENCE);
        $properties->name = Schema::string()
            ->setDescription('The name of the plugin');
        $properties->version = Schema::string()
            ->setDescription('The version of the plugin');
        $properties->url = Schema::string()
            ->setDescription('The url of the plugin')
            ->setFormat(Format::URI_REFERENCE);
        $properties->author = Schema::string()
            ->setDescription('The author of the plugin');
        $properties->author_url = Schema::string()
            ->setDescription('The author url of the plugin')
            ->setFormat(Format::URI_REFERENCE);
        $properties->license = Schema::string()
            ->setDescription('The license of the plugin');
        $properties->license_url = Schema::string()
            ->setDescription('The license url of the plugin')
            ->setFormat(Format::URI_REFERENCE);
        $properties->enable_admin_page = Schema::boolean()
            ->setDescription('Enable Page on addon plugin')
            ->setDefault(false);
        $properties->namespace = Schema::string()
            ->setDescription('The namespace of the plugin class')
            ->setPattern(
                '^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*([\\\\/][a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$'
            );
    }

    /**
     * Get the id of the plugin
     *
     * @return string The id of the plugin
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the name of the plugin
     *
     * @return string The name of the plugin
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the version of the plugin
     *
     * @return string The version of the plugin
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the author of the plugin
     *
     * @return string The author of the plugin
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Get the author url of the plugin
     *
     * @return string The author url of the plugin
     */
    public function getAuthorUrl(): string
    {
        return $this->author_url;
    }

    /**
     * Get the license of the plugin
     *
     * @return string The license of the plugin
     */
    public function getLicense(): string
    {
        return $this->license;
    }

    /**
     * Get the license url of the plugin
     *
     * @return string The license url of the plugin
     */
    public function getLicenseUrl(): string
    {
        return $this->license_url;
    }

    /**
     * Get namespace of the plugin
     *
     * @return ?string
     */
    public function getNamespace(): ?string
    {
        return $this->namespace ? str_replace('/', '\\', $this->namespace) : null;
    }

    /**
     * Check if the plugin has admin page
     *
     * @return bool
     */
    public function isEnableAdminPage(): bool
    {
        return $this->enable_admin_page;
    }

    /**
     * Get plugin classname
     *
     * @return string|null
     */
    public function getPluginClassName() : ?string
    {
        $namespace = $this->getNamespace();
        if (!$namespace) {
            return null;
        }
        return $namespace . '\\' . $this->getName();
    }
}
