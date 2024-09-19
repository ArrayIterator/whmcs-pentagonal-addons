<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Hidden extends ClassStructure
{
    public string $type = 'hidden';

    public string $value;

    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType(JsonSchema::STRING)
            ->setDescription('The hidden input of the definition')
            ->setAdditionalProperties(false)
            ->setRequired([
                'value',
            ]);

        $properties->value = Schema::string()
            ->setDescription('The value of the hidden input')
            ->setType(JsonSchema::STRING);
        $properties->type = Schema::string()
            ->setDescription('The type of the hidden input')
            ->setDefault('hidden')
            ->setEnum([
                'hidden',
            ]);
    }
}
