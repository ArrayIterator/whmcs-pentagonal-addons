<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures;

use Pentagonal\Neon\WHMCS\Addon\Schema\Abstracts\AbstractStructure;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Definition;
use stdClass;
use Swaggest\JsonSchema\Constraint\Format;
use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;

class Themes extends AbstractStructure
{
    /**
     * @var string The schema uri of the schema id
     */
    public const ID = 'https://hub.pentagonal.org/schemas/options+themes.json';

    /**
     * @var string The version of the schema
     */
    public const VERSION = '1.0.0';

    /**
     * @var string The schema uri of the schema
     */
    public string $schema = self::ID;

    /**
     * @var string The id of the schema
     */
    public string $id = '';

    /**
     * @var string The name of the schema
     */
    public string $name;

    /**
     * @var string The version of the schema
     */
    public string $url = '';

    /**
     * @var string The author of the schema
     */
    public string $author = '';

    /**
     * @var string The author url of the schema
     */
    public string $author_url = '';

    /**
     * @var string The license of the schema
     */
    public string $license = '';

    /**
     * @var string The license url of the schema
     */
    public string $license_url = '';

    /**
     * @var ?string The date of created of the schema
     */
    public ?string $date = null;

    /**
     * @var ?string The date of updated of the schema
     */
    public ?string $updated = null;

    /**
     * @var string The description of the schema
     */
    public string $description = '';

    /**
     * @var string The language directory of the schema
     */
    public string $language_directory = 'languages';

    /**
     * @var bool Enable or disable hook
     */
    public bool $translate = true;

    /**
     * @var bool Enable or disable hook
     */
    public bool $hooks = true;

    /**
     * @var bool Enable or disable services
     */
    public bool $services = true;

    /**
     * @var array The changelog of the schema
     */
    public array $changelog = [];

    /**
     * @var object The metadata of the schema
     */
    public object $metadata;

    /**
     * @var array|object $settings
     */
    public $settings = null;

    /**
     * Themes constructor.
     */
    public function __construct()
    {
        $this->schema = self::SCHEMA;
        $this->id = self::ID;
        $this->metadata = new stdClass();
    }

    /**
     * @InheritDoc
     * @noinspection PhpUndefinedFieldInspection
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema->{Schema::PROP_ID} = self::ID;
        $ownerSchema->version = self::VERSION;
        /** @noinspection PhpParamsInspection */
        $ownerSchema
            ->setSchema(self::SCHEMA)
            //->setId(self::ID) // bug
            ->setTitle('Schema for Themes')
            ->setDescription('The schema for Themes')
            ->setType(JsonSchema::OBJECT)
            ->setAdditionalProperties(false)
            ->setDefinitions(Definition::schema())
            ->setRequired([
                'name'
            ])
            ->addPropertyMapping('$schema', 'schema')
            ->addPropertyMapping(Schema::PROP_ID, 'id');

        $properties->schema = Schema::string()
            //->setFormat(Format::URI) // disabled to allow path
            ->setDescription('The schema uri of the theme');
        $properties->id = Schema::string()
            ->setDescription('The id of the theme schema')
            ->setFormat(Format::URI);
        $properties->name = Schema::string()
            ->setDescription('The name of the theme');
        $properties->version = Schema::string()
            ->setDescription('The version of the theme');
        $properties->url = Schema::string()
            ->setDescription('The url of the theme')
            ->setFormat(Format::URI);
        $properties->author = Schema::string()
            ->setDescription('The author of the theme');
        $properties->author_url = Schema::string()
            ->setDescription('The author url of the theme')
            ->setFormat(Format::URI);
        $properties->license = Schema::string()
            ->setDescription('The license of the theme');
        $properties->license_url = Schema::string()
            ->setDescription('The license url of the theme')
            ->setFormat(Format::URI);
        $properties->date = Schema::string()
            ->setDescription('The date of created of the theme')
            ->setFormat(Format::DATE_TIME);
        $properties->updated = Schema::string()
            ->setDescription('The date of updated of the theme')
            ->setFormat(Format::DATE_TIME);
        $properties->description = Schema::string()
            ->setDescription('The description of the theme');
        $properties->language_directory = Schema::string()
            ->setDescription('The language directory of the theme')
            ->setDefault('languages');
        $properties->hooks = Schema::boolean()
            ->setDescription('Enable or disable hook')
            ->setDefault(true);
        $properties->services = Schema::boolean()
            ->setDescription('Enable or disable services')
            ->setDefault(true);
        $properties->translate = Schema::boolean()
            ->setDescription('The translatable or not of the theme')
            ->setDefault(true);
        $properties->changelog = Schema::create()
            ->setType(JsonSchema::_ARRAY)
            ->setDescription('The changelog of the theme')
            ->setItems(
                Schema::object()
                    ->setAdditionalProperties(true)
                    ->setProperty('version', Schema::string())
                    ->setProperty('date', Schema::string()->setFormat(Format::DATE_TIME))
                    ->setProperty('description', Schema::string())
            )
            ->setDefault([]);
        $properties->metadata = Schema::object()
            ->setDescription('The metadata of the theme')
            ->setAdditionalProperties(true)
            ->setDefault(new stdClass());

        $properties->settings = Schema::create()
            ->setType([
                JsonSchema::OBJECT,
                JsonSchema::_ARRAY,
            ])->setDescription('The settings for the theme')
            ->setItems(Schema::object()->setRef('#/definitions/settings'))
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/settings'));
    }

    /**
     * Get schema id
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get theme name
     *
     * @return ?string
     */
    public function getName(): ?string
    {
        return $this->name??null;
    }

    /**
     * Get theme url
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Get author url
     *
     * @return string
     */
    public function getAuthorUrl(): string
    {
        return $this->author_url;
    }

    /**
     * Get license
     *
     * @return string
     */
    public function getLicense(): string
    {
        return $this->license;
    }

    /**
     * Get license url
     *
     * @return string
     */
    public function getLicenseUrl(): string
    {
        return $this->license_url;
    }

    /**
     * Get date
     *
     * @return ?string return null if not set
     */
    public function getDate(): ?string
    {
        return $this->date??null;
    }

    /**
     * Get updated
     *
     * @return ?string return null if not set
     */
    public function getUpdated(): ?string
    {
        return $this->updated??null;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get language directory
     *
     * @return string
     */
    public function getLanguageDirectory(): string
    {
        return $this->language_directory;
    }

    /**
     * Get translate
     *
     * @return bool
     */
    public function isTranslate(): bool
    {
        return $this->translate;
    }

    /**
     * Get hooks enabled or disabled
     *
     * @return bool
     */
    public function isHooks(): bool
    {
        return $this->hooks;
    }

    /**
     * Get services enabled or disabled
     *
     * @return bool
     */
    public function isServices(): bool
    {
        return $this->services;
    }

    /**
     * Get changelog
     *
     * @return array
     */
    public function getChangelog(): array
    {
        return $this->changelog;
    }

    /**
     * Get metadata
     *
     * @return object|stdClass
     */
    public function getMetadata(): object
    {
        return $this->metadata;
    }

    /**
     * Get settings
     *
     * @return array|object
     */
    public function getSettings()
    {
        return $this->settings;
    }
}
