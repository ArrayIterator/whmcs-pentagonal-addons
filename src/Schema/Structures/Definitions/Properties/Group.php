<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties;

use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Group extends ClassStructure
{
    public object $group;

    public string $type = 'group';

    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType(JsonSchema::OBJECT)
            ->setDescription('The group attributes of the definition')
            ->setAdditionalProperties(Schema::object()->setRef('#/definitions/htmlTagAttributes'))
            ->setRequired([
                'group',
            ]);
        $properties->group = Schema::object()
            ->setDescription('The group of the definition')
            ->setRef('#/definitions/settings');
        $properties->type = Schema::string()
            ->setDescription('The type of the group')
            ->setDefault('group')
            ->setEnum([
                'group',
            ]);
    }
}
