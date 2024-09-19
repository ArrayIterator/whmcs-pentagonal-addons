<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Title extends ClassStructure
{
    public string $title;

    public string $tag = 'h2';

    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType(JsonSchema::STRING)
            ->setDescription('The title of the definition')
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/htmlTagAttributes'))
            ->setRequired([
                'title',
            ]);
        $properties->title = Schema::string()
            ->setDescription('The title of the definition')
            ->setType(JsonSchema::STRING);
        $properties->tag = Schema::string()
            ->setDescription('The tag of the title')
            ->setType(JsonSchema::STRING)
            ->setEnum([
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
                'div',
            ])
            ->setDefault('h2');
    }
}
