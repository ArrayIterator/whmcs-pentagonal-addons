<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Html extends ClassStructure
{
    public string $html;

    public string $type = 'html';

    public static function setUpProperties($properties, Schema $ownerSchema)
    {

        $ownerSchema
            ->setType(JsonSchema::OBJECT)
            ->setDescription('The html attributes of the definition')
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/htmlTagAttributes'))
            ->setRequired([
                'html',
            ]);

        $properties->html = Schema::string()
            ->setDescription('The html of the definition')
            ->setType(JsonSchema::STRING);
        $properties->type = Schema::string()
            ->setDescription('The type of the html')
            ->setDefault('html')
            ->setEnum([
                'html',
            ]);
    }
}
