<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use stdClass;
use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Radio extends ClassStructure
{

    public string $name;

    public string $label;

    public string $value;

    public string $description= '';

    public bool $multiple = false;

    public bool $required = false;

    public bool $checked = false;

    public string $type = 'radio';

    public object $attributes;

    public function __construct()
    {
        $this->attributes = new stdClass();
    }

    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType(JsonSchema::OBJECT)
            ->setDescription('The radio attributes of the definition')
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/htmlTagAttributes'))
            ->setRequired([
                'label',
                'name',
                'value',
            ]);

        $properties->multiple = Schema::boolean()
            ->setDescription('The multiple attribute of the radio')
            ->setDefault(false);
        $properties->required = Schema::boolean()
            ->setDescription('The required attribute of the radio')
            ->setDefault(false);
        $properties->checked = Schema::boolean()
            ->setDescription('The checked attribute of the radio')
            ->setDefault(false);
        $properties->label = Schema::string()
            ->setDescription('The label of the radio');
        $properties->description = Schema::string()
            ->setDescription('The description of the radio');
        $properties->name = Schema::string()
            ->setDescription('The name of the radio');
        $properties->value = Schema::string()
            ->setDescription('The value of the radio');
        $properties->type = Schema::string()
            ->setDescription('The type of the radio')
            ->setDefault('radio')
            ->setEnum([
                'radio',
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
