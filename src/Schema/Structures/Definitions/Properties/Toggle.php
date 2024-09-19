<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use stdClass;
use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Toggle extends ClassStructure
{

    public string $label;

    public string $name;

    public bool $multiple = false;

    public bool $required = false;

    public bool $checked = false;

    public string $value = '1';

    public string $description = '';

    public string $type = 'toggle';

    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType(JsonSchema::OBJECT)
            ->setDescription('The toggle checkbox attributes of the definition')
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/htmlTagAttributes'))
            ->setRequired([
                'label',
                'name',
                'value',
            ]);
        $properties->multiple = Schema::boolean()
            ->setDescription('The multiple attribute of the toggle')
            ->setEnum([
                false
            ]);
        $properties->required = Schema::boolean()
            ->setDescription('The required attribute of the toggle')
            ->setEnum([
                false
            ]);
        $properties->checked = Schema::boolean()
            ->setDescription('The checked attribute of the toggle')
            ->setDefault(false);
        $properties->label = Schema::string()
            ->setDescription('The label of the toggle');
        $properties->description = Schema::string()
            ->setDescription('The description of the toggle');
        $properties->name = Schema::string()
            ->setDescription('The name of the toggle');
        $properties->value = Schema::string()
            ->setDescription('The value of the toggle')
            ->setDefault('1')
            ->setEnum([
                '1',
                '0',
            ]);
        $properties->type = Schema::string()
            ->setDescription('The type of the toggle')
            ->setDefault('toggle')
            ->setEnum([
                'toggle',
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
