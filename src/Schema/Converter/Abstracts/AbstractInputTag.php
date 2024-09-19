<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Abstracts;

use Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces\TagInputInterface;

abstract class AbstractInputTag extends AbstractTag implements TagInputInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->contract['name'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function hasDefaultValue(): bool
    {
        return isset($this->contract['default']);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultValue()
    {
        return $this->contract['default'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function isRequired(): bool
    {
        return isset($this->contract['required']) && $this->contract['required'] === true;
    }

    /**
     * @inheritDoc
     */
    public function isDisabled(): bool
    {
        return isset($this->contract['disabled']) && $this->contract['disabled'] === true;
    }

    /**
     * @inheritDoc
     */
    public function isReadonly(): bool
    {
        return isset($this->contract['readonly']) && $this->contract['readonly'] === true;
    }

    /**
     * @inheritDoc
     */
    public function isMultiple(): bool
    {
        return isset($this->contract['multiple']) && $this->contract['multiple'] === true;
    }

    /**
     * @inheritDoc
     */
    public function isValid(): bool
    {
        return isset($this->contract['name']);
    }

    /**
     * @inheritDoc
     */
    public function getValue()
    {
        return $this->contract['value'] ?? null;
    }
}
