<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures;

use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Definition;
use stdClass;
use Swaggest\JsonSchema\Constraint\Format;
use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Themes extends ClassStructure
{
    public const SCHEMA = 'http://json-schema.org/draft-07/schema';
    public const ID = 'https://hub.pentagonal.org/schemas/options+themes.json';
    public const VERSION = '1.0.0';

    public string $schema;
    public string $id;
    public string $name;
    public string $version = '';
    public string $url = '';
    public string $author = '';
    public string $author_url = '';
    public string $license = '';
    public string $license_url = '';
    public string $date;
    public string $updated;
    public string $description;
    public string $language_directory = 'languages';
    public bool $translate = true;
    public bool $hooks = true;
    public bool $services = true;
    public array $changelog = [];
    public object $metadata;

    /**
     * @var array|object $settings
     */
    public $settings = [];

    public function __construct()
    {
        $this->schema = self::SCHEMA;
        $this->id = self::ID;
        $this->metadata = new stdClass();
    }

    /**
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
            ->setDescription('The schema')
            ->setType(JsonSchema::OBJECT)
            ->setAdditionalProperties(false)
            ->setDefinitions(Definition::schema())
            ->setRequired([
                'name'
            ])
            ->addPropertyMapping('$schema', 'schema')
            ->addPropertyMapping(Schema::PROP_ID, 'id');

        $properties->schema = Schema::string()
//            ->setFormat(Format::URI)
            ->setDescription('The schema uri of the schema');
        $properties->id = Schema::string()
            ->setDescription('The id of the schema')
            ->setFormat(Format::URI);
        $properties->name = Schema::string()
            ->setDescription('The name of the schema');
        $properties->version = Schema::string()
            ->setDescription('The version of the schema');
        $properties->url = Schema::string()
            ->setDescription('The url of the schema')
            ->setFormat(Format::URI);
        $properties->author = Schema::string()
            ->setDescription('The author of the schema');
        $properties->author_url = Schema::string()
            ->setDescription('The author url of the schema')
            ->setFormat(Format::URI);
        $properties->license = Schema::string()
            ->setDescription('The license of the schema');
        $properties->license_url = Schema::string()
            ->setDescription('The license url of the schema')
            ->setFormat(Format::URI);
        $properties->date = Schema::string()
            ->setDescription('The date of created of the schema')
            ->setFormat(Format::DATE_TIME);
        $properties->updated = Schema::string()
            ->setDescription('The date of updated of the schema')
            ->setFormat(Format::DATE_TIME);
        $properties->description = Schema::string()
            ->setDescription('The description of the schema');
        $properties->language_directory = Schema::string()
            ->setDescription('The language directory of the schema')
            ->setDefault('languages');
        $properties->hooks = Schema::boolean()
            ->setDescription('Enable or disable hook')
            ->setDefault(true);
        $properties->services = Schema::boolean()
            ->setDescription('Enable or disable services')
            ->setDefault(true);
        $properties->translate = Schema::boolean()
            ->setDescription('The translatable or not of the schema')
            ->setDefault(true);
        $properties->changelog = Schema::create()
            ->setType(JsonSchema::_ARRAY)
            ->setDescription('The changelog of the schema')
            ->setItems(
                Schema::object()
                    ->setAdditionalProperties(true)
                    ->setProperty('version', Schema::string())
                    ->setProperty('date', Schema::string()->setFormat(Format::DATE_TIME))
                    ->setProperty('description', Schema::string())
            )
            ->setDefault([]);
        $properties->metadata = Schema::object()
            ->setDescription('The metadata of the schema')
            ->setAdditionalProperties(true)
            ->setDefault(new stdClass());

        $properties->settings = Schema::create()
            ->setType([
                JsonSchema::OBJECT,
                JsonSchema::_ARRAY,
            ])->setDescription('The settings for the schema')
            ->setItems(Schema::object()->setRef('#/definitions/settings'))
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/settings'));
    }
}
