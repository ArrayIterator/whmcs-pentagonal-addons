<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use stdClass;
use Swaggest\JsonSchema\Constraint\Format;
use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Input extends ClassStructure
{
    public string $name;

    public string $type = 'input';

    public string $input = 'text';

    public string $description = '';

    public string $default = '';

    public ?string $label = null;

    public string $placeholder = '';

    public string $value = '';

    public bool $required = false;

    public bool $disabled = false;

    public bool $readonly = false;

    public bool $multiple = false;

    public ?string $pattern = null;

    public ?int $minlength = null;

    public ?int $maxlength = null;

    public ?int $min = null;

    public ?int $max = null;

    public ?int $step = null;

    public ?int $size = null;

    public object $attributes;

    public function __construct()
    {
        $this->attributes = new stdClass();
    }

    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType(JsonSchema::OBJECT)
            ->setDescription('The input attributes of the definition')
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/htmlTagAttributes'))
            ->setRequired([
                'name',
            ]);

        $properties->type = Schema::string()
            ->setDescription('The type of the input')
            ->setDefault('input')
            ->setEnum([
                'input',
            ]);
        $properties->name = Schema::string()
            ->setDescription('The name of the input');
        $properties->input = Schema::string()
            ->setDescription('The input type of the input')
            ->setDefault('text')
            ->setEnum([
                'text',
                'password',
                'email',
                'number',
                'tel',
                'url',
                'search',
                'date',
                'time',
                'datetime-local',
                'month',
                'week',
                'color',
                'file',
                'hidden',
                'image',
                'range',
                'reset',
                'submit',
            ]);
        $properties->placeholder = Schema::string()
            ->setDescription('The placeholder of the input');
        $properties->value = Schema::string()
            ->setDescription('The value of the input');
        $properties->required = Schema::boolean()
            ->setDescription('The required attribute of the input')
            ->setDefault(false);
        $properties->disabled = Schema::boolean()
            ->setDescription('The disabled attribute of the input')
            ->setDefault(false);
        $properties->readonly = Schema::boolean()
            ->setDescription('The readonly attribute of the input')
            ->setDefault(false);
        $properties->multiple = Schema::boolean()
            ->setDescription('The multiple attribute of the input')
            ->setDefault(false);
        $properties->pattern = Schema::string()
            ->setFormat(Format::REGEX)
            ->setDescription('The pattern attribute of the input')
            ->setDefault(null);
        $properties->label = Schema::create()
            ->setType([
                JsonSchema::STRING,
                JsonSchema::NULL,
            ])
            ->setDescription('The label of the input')
            ->setDefault(null);
        $properties->default = Schema::string()
            ->setDescription('The default value of the input')
            ->setDefault('');
        $properties->description = Schema::string()
            ->setDescription('The description of the input')
            ->setDefault('');
        $properties->minlength = Schema::create()
            ->setDescription('The minlength attribute of the input')
            ->setType([
                JsonSchema::INTEGER,
                JsonSchema::NULL,
            ])
            ->setDefault(null);
        $properties->maxlength = Schema::create()
            ->setDescription('The maxlength attribute of the input')
            ->setType([
                JsonSchema::INTEGER,
                JsonSchema::NULL,
            ])
            ->setDefault(null);
        $properties->min = Schema::create()
            ->setDescription('The min attribute of the input')
            ->setType([
                JsonSchema::INTEGER,
                JsonSchema::NULL,
            ])
            ->setDefault(null);
        $properties->max = Schema::create()
            ->setDescription('The max attribute of the input')
            ->setType([
                JsonSchema::INTEGER,
                JsonSchema::NULL,
            ])
            ->setDefault(null);
        $properties->step = Schema::create()
            ->setDescription('The step attribute of the input')
            ->setType([
                JsonSchema::INTEGER,
                JsonSchema::NULL,
            ])
            ->setDefault(null);
        $properties->size = Schema::create()
            ->setDescription('The size attribute of the input')
            ->setType([
                JsonSchema::INTEGER,
                JsonSchema::NULL,
            ])
            ->setDefault(null);
        $properties->attributes = Schema::object()
            ->setDescription('The attributes')
            ->setNot(
                Schema::object()->setRef('#/definitions/htmlTagAttributes')
            )
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
