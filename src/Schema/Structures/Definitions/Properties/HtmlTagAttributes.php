<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class HtmlTagAttributes extends ClassStructure
{
    public object $data;

    public object $aria;

    public object $class;

    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType(JsonSchema::OBJECT)
            ->setDescription('The html tag attributes of the definition')
            ->setAdditionalProperties(false);

        $properties->data = Schema::object()
            ->setDescription('The data-* attributes of the tag')
            ->setAdditionalProperties(false)
            ->setPatternProperties([
                '^([a-z]+([a-z0-9-]*[a-z]+)?)$' => Schema::object()->setType([
                    JsonSchema::STRING,
                    JsonSchema::NUMBER,
                    JsonSchema::BOOLEAN,
                    JsonSchema::NULL,
                ]),
            ])
            ->setMinProperties(1);

        $properties->aria = Schema::object()
            ->setDescription('The aria-* attributes of the tag')
            ->setAdditionalProperties(false)
            ->setPatternProperties([
                '^([a-z]+([a-z0-9-]*[a-z]+)?)$' => Schema::object()->setType([
                    JsonSchema::STRING,
                    JsonSchema::NUMBER,
                    JsonSchema::BOOLEAN,
                    JsonSchema::NULL,
                ]),
            ])
            ->setMinProperties(1);

        $properties->class = Schema::object()
            ->setDescription('The class attribute of the tag')
            ->setOneOf([
                Schema::object()->setType(JsonSchema::STRING)
                    ->setPattern('^([a-z0-9:-]+)$')
                    ->setDescription('The class attribute of the tag'),
                Schema::object()->setType(JsonSchema::_ARRAY)->setItems(
                    Schema::object()->setType(JsonSchema::STRING)
                        ->setDescription('The class list attribute of the tag')
                        ->setPattern('^([a-z0-9:-]+)$')
                        ->setMinItems(1)
                ),
            ]);
    }
}
