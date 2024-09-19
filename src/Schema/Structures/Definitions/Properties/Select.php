<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use stdClass;
use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Select extends ClassStructure
{
    public string $name;

    public string $label;

    public string $type = 'select';

    public string $description = '';

    public bool $multiple = false;

    public bool $required = false;

    public string $placeholder = '';

    public array $options = [];
    public object $attributes;

    public function __construct()
    {
        $this->attributes = new stdClass();
    }

    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType(JsonSchema::OBJECT)
            ->setDescription('The select attributes of the definition')
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/htmlTagAttributes'))
            ->setRequired([
                'name',
                'label',
                'options',
            ]);

        $properties->name = Schema::string()
            ->setDescription('The name of the select');
        $properties->label = Schema::string()
            ->setDescription('The label of the select');
        $properties->description = Schema::string()
            ->setDescription('The description of the select');
        $properties->value = Schema::string()
            ->setDescription('The value of the select');
        $properties->placeholder = Schema::string()
            ->setDescription('The placeholder of the select');
        $properties->multiple = Schema::boolean()
            ->setDescription('The multiple attribute of the select')
            ->setDefault(false);
        $properties->required = Schema::boolean()
            ->setDescription('The required attribute of the select')
            ->setDefault(false);
        $properties->options = Schema::create()
            ->setOneOf([
                Schema::object()
                    ->setRequired([
                        'label',
                        'value',
                    ])
                    ->setItems(
                        Schema::create()
                            ->setType(JsonSchema::_ARRAY)
                            ->setAdditionalProperties(false)
                            ->setProperty('label', Schema::string())
                            ->setProperty('value', Schema::string())
                            ->setProperty('disabled', Schema::boolean())
                            ->setProperty('selected', Schema::boolean())
                            ->setRequired([
                                'label',
                                'value',
                            ])
                    )
                    ->setDescription('The options of the select'),
                Schema::object()
                    ->setDescription('The optgroup of the select')
                    ->setAdditionalProperties(false)
                    ->setProperty(
                        'label',
                        Schema::string()
                            ->setDescription('Label of optgroup')
                    )
                    ->setProperty('type', Schema::string()->setDefault('optgroup'))
                    ->setProperty('options', Schema::create()->setRef('#/definitions/select/properties/options'))
                    ->setRequired([
                        'label',
                        'type',
                        'options',
                    ])
            ])
            ->setType(JsonSchema::_ARRAY);
        $properties->type = Schema::string()
            ->setDescription('The type of the select')
            ->setDefault('select')
            ->setEnum([
                'select',
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
