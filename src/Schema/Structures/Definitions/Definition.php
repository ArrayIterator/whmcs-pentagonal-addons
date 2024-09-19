<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions;

use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\Checkbox;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\Group;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\Hidden;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\Html;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\HtmlTagAttributes;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\Input;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\Radio;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\Select;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\Spacer;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\Text;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\TextArea;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\Title;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Properties\Toggle;
use Pentagonal\Neon\WHMCS\Addon\Schema\Structures\Definitions\Types\SettingsType;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Structure\ClassStructure;

class Definition extends ClassStructure
{
    public static function setUpProperties($properties, Schema $ownerSchema)
    {
        $ownerSchema->settings = SettingsType::schema();
//        $ownerSchema->tagName = TagNameType::schema();
        $ownerSchema->htmlTagAttributes = HtmlTagAttributes::schema();
        $ownerSchema->checkBox = Checkbox::schema();
        $ownerSchema->toggle = Toggle::schema();
        $ownerSchema->radio = Radio::schema();
        $ownerSchema->html = Html::schema();
        $ownerSchema->title = Title::schema();
        $ownerSchema->group = Group::schema();
        $ownerSchema->spacer = Spacer::schema();
        $ownerSchema->textarea = TextArea::schema();
        $ownerSchema->text = Text::schema();
        $ownerSchema->hidden = Hidden::schema();
        $ownerSchema->select = Select::schema();
        $ownerSchema->input = Input::schema();
    }
}
