<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Tags;

use Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Abstracts\AbstractInputTag;
use Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces\TagCheckAbleInterface;

class Checkbox extends AbstractInputTag implements TagCheckAbleInterface
{

    public function isChecked(): bool
    {
        return ($this->contract['checked'] ?? false) === true;
    }

    public function getTagName(): string
    {
        return 'input';
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'checkbox';
    }
}
