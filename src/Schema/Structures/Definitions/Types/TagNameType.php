<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Types;

use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class TagNameType extends ClassStructure
{
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setDescription('The tag name of the attribute')
            ->setPattern('^([a-z]+([a-z0-9-]*[a-z]+)?)')
            ->setType(JsonSchema::STRING);
    }
}
