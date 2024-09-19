<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use stdClass;
use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class TextArea extends ClassStructure
{
    public string $name;
    public string $label;
    public string $description = '';
    public string $default = '';
    public string $placeholder = '';
    public string $value = '';
    public string $type = 'textarea';
    public bool $required = false;
    public bool $disabled = false;
    public bool $readonly = false;
    public ?int $rows = null;
    public ?int $cols = null;
    public ?int $maxlength = null;
    public ?int $minlength = null;
    public object $attributes;

    public function __construct()
    {
        $this->attributes = new stdClass();
    }

    /**
     * @throws \Exception
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType(JsonSchema::OBJECT)
            ->setDescription('The textarea attributes of the definition')
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/htmlTagAttributes'))
            ->setRequired([
                'name',
                'label',
            ]);
        $properties->type = Schema::string()
            ->setDescription('The type of the textarea')
            ->setDefault('textarea')
            ->setEnum([
                'textarea',
            ]);
        $properties->name = Schema::string()
            ->setDescription('The name of the textarea');
        $properties->label = Schema::string()
            ->setDescription('The label of the textarea');
        $properties->description = Schema::string()
            ->setDescription('The description of the textarea');
        $properties->default = Schema::string()
            ->setDescription('The default value of the textarea');
        $properties->placeholder = Schema::string()
            ->setDescription('The placeholder of the textarea');
        $properties->value = Schema::string()
            ->setDescription('The value of the textarea');
        $properties->required = Schema::boolean()
            ->setDescription('The required attribute of the textarea')
            ->setDefault(false);
        $properties->disabled = Schema::boolean()
            ->setDescription('The disabled attribute of the textarea')
            ->setDefault(false);
        $properties->readonly = Schema::boolean()
            ->setDescription('The readonly attribute of the textarea')
            ->setDefault(false);
        $properties->rows = Schema::create()
            ->setDescription('The rows of the textarea')
            ->setType([
                JsonSchema::INTEGER,
                JsonSchema::NULL,
            ]);
        $properties->cols = Schema::integer()
            ->setDescription('The cols of the textarea')
            ->setType([
                JsonSchema::INTEGER,
                JsonSchema::NULL,
            ]);
        $properties->maxlength = Schema::create()
            ->setDescription('The maxlength attribute of the textarea')
            ->setType([
                JsonSchema::INTEGER,
                JsonSchema::NULL,
            ]);
        $properties->minlength = Schema::create()
            ->setDescription('The minlength attribute of the textarea')
            ->setType([
                JsonSchema::INTEGER,
                JsonSchema::NULL,
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
