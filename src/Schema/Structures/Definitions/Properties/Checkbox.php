<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use stdClass;
use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Checkbox extends ClassStructure
{

    public string $name;

    public string $label;

    public string $value;

    public string $description = '';

    public bool $multiple = false;

    public bool $required = false;

    public bool $checked = false;

    public string $type = 'checkbox';
    public object $attributes;

    public function __construct()
    {
        $this->attributes = new stdClass();
    }

    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType(JsonSchema::OBJECT)
            ->setDescription('The checkbox attributes of the definition')
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/htmlTagAttributes'))
            ->setRequired([
                'label',
                'name',
                'value'
            ]);

        $properties->multiple = Schema::boolean()
            ->setDescription('The multiple attribute of the checkbox')
            ->setDefault(false);
        $properties->required = Schema::boolean()
            ->setDescription('The required attribute of the checkbox')
            ->setDefault(false);
        $properties->checked = Schema::boolean()
            ->setDescription('The checked attribute of the checkbox')
            ->setDefault(false);
        $properties->label = Schema::string()
            ->setDescription('The label of the checkbox');
        $properties->description = Schema::string()
            ->setDescription('The description of the checkbox');
        $properties->name = Schema::string()
            ->setDescription('The name of the checkbox');
        $properties->value = Schema::string()
            ->setDescription('The value of the checkbox');
        $properties->type = Schema::string()
            ->setDescription('The type of the checkbox')
            ->setDefault('checkbox')
            ->setEnum([
                'checkbox',
            ]);

        $properties->attributes = Schema::object()
            ->setDescription('The attributes')
            ->setPatternProperty(
                '^([a-z]+([a-z0-9-]*[a-z]+)?)$',
                Schema::create()->setType([
                    JsonSchema::STRING,
                    JsonSchema::INTEGER,
                    JsonSchema::BOOLEAN,
                    JsonSchema::NULL,
                ])
            )->setDefault(new stdClass());
    }
}
