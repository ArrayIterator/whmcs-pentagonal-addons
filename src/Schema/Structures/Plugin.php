<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures;

use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Definition;
use Swaggest\JsonSchema\Constraint\Format;
use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Plugin extends ClassStructure
{
    public const SCHEMA = 'http://json-schema.org/draft-07/schema';
    public const ID = 'https://hub.pentagonal.org/schemas/plugin.json';
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
    public string $plugin_file = 'plugin.php';

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
                'plugin_file',
            ])
            ->addPropertyMapping('$schema', 'schema')
            ->addPropertyMapping(Schema::PROP_ID, 'id');

        $properties->schema = Schema::string()
//            ->setFormat(Format::URI)
            ->setDescription('The schema uri of the plugin');
        $properties->id = Schema::string()
            ->setDescription('The id of the plugin')
            ->setFormat(Format::URI);
        $properties->name = Schema::string()
            ->setDescription('The name of the plugin');
        $properties->version = Schema::string()
            ->setDescription('The version of the plugin');
        $properties->url = Schema::string()
            ->setDescription('The url of the plugin')
            ->setFormat(Format::URI);
        $properties->author = Schema::string()
            ->setDescription('The author of the plugin');
        $properties->author_url = Schema::string()
            ->setDescription('The author url of the plugin')
            ->setFormat(Format::URI);
        $properties->license = Schema::string()
            ->setDescription('The license of the plugin');
        $properties->license_url = Schema::string()
            ->setDescription('The license url of the plugin')
            ->setFormat(Format::URI);
        $properties->plugin_file = Schema::string()
            ->setDescription('The plugin file')
            ->setPattern('^[a-z0-9_\-\.]+\.php$')
            ->setDefault('plugin.php');
    }
}
