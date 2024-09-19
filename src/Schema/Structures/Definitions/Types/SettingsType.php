<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Types;

use Swaggest\JsonSchema\JsonSchema;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class SettingsType extends ClassStructure
{
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema
            ->setType([
                JsonSchema::OBJECT,
                JsonSchema::_ARRAY,
            ])
            ->setDescription('The settings of the definition')
            ->setOneOf([
                Schema::object()->setRef('#/definitions/checkBox'),
                Schema::object()->setRef('#/definitions/radio'),
                Schema::object()->setRef('#/definitions/toggle'),
                Schema::object()->setRef('#/definitions/html'),
                Schema::object()->setRef('#/definitions/group'),
                Schema::object()->setRef('#/definitions/title'),
                Schema::object()->setRef('#/definitions/spacer'),
                Schema::object()->setRef('#/definitions/text'),
                Schema::object()->setRef('#/definitions/hidden'),
                Schema::object()->setRef('#/definitions/textarea'),
                Schema::object()->setRef('#/definitions/select'),
                Schema::object()->setRef('#/definitions/input'),
            ]);
    }
}
