<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Text extends ClassStructure
{
    public string $text;

    public string $type = 'text';

    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType(JsonSchema::STRING)
            ->setDescription('The text of the definition')
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/htmlTagAttributes'))
            ->setRequired([
                'text',
            ]);
        $properties->text = Schema::string()
            ->setDescription('The text of the definition')
            ->setType(JsonSchema::STRING);
        $properties->type = Schema::string()
            ->setDescription('The type of the text')
            ->setDefault('text')
            ->setEnum([
                'text',
            ]);
    }
}
