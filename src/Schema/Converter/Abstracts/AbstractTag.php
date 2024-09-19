<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Abstracts;

use Illuminate\Support\Stringable;
use Pentagonal\Neon\WHMCS\Addon\Helpers\HtmlAttributes;
use Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces\TagInputInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces\TagInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Converter\SettingConverter;
use Swaggest\JsonSchema\Structure\ObjectItemContract;
use function is_object;
use function is_string;
use function method_exists;

abstract class AbstractTag implements TagInterface
{
    /**
     * @var SettingConverter $converter the converter
     */
    protected SettingConverter $converter;

    /**
     * @var ObjectItemContract $contract Contract of the tag
     */
    protected ObjectItemContract $contract;

    /**
     * @var ?TagInterface $parent Parent tag if any
     */
    protected ?TagInterface $parent;

    /**
     * @inheritDoc
     */
    public function __construct(
        SettingConverter $converter,
        ObjectItemContract $contract,
        ?TagInterface $parent = null
    ) {
        $this->converter = $converter;
        $this->contract = $contract;
        $this->parent = $parent;
    }

    /**
     * @inheritDoc
     */
    public function getConverter(): SettingConverter
    {
        return $this->converter;
    }

    /**
     * @inheritDoc
     */
    public function getContract(): ObjectItemContract
    {
        return $this->contract;
    }

    /**
     * @inheritDoc
     */
    public function getParent(): ?TagInterface
    {
        return $this->parent;
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): ?string
    {
        return $this->contract['label'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): ?string
    {
        return $this->contract['title'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getTagAttributes(): array
    {
        $attributes = $this->contract['attributes'] ?? [];
        if (! is_array($attributes)) {
            return [];
        }
        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return $this->contract['description'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        if (!$this->getConverter()->isValid()) {
            return '';
        }
        return $this->toHtml();
    }

    /**
     * @inheritDoc
     */
    public function toHtml(?string $wrapper = null, array $attributes = []): string
    {
        if (!$this->getConverter()->isValid()) {
            return '';
        }
        if (!$this->getContract()->valid()) {
            return '';
        }
        $tagAttributes = $this->getTagAttributes();
        $content = $tagAttributes['html'] ?? (
            $tagAttributes['text'] ?? ''
        );
        $isTagInput = $this instanceof TagInputInterface;
        if ($isTagInput) {
            $tagAttributes['type'] = $this->getType();
        }
        $content = is_string($content)
            || $content instanceof Stringable
            || is_object($content) && method_exists($content, '__toString') ? (string) $content : '';
        unset($tagAttributes['html'], $tagAttributes['text']);
        $tagName = $this->getTagName();
        if ($tagName === 'textarea') {
            unset($tagAttributes['value'], $tagAttributes['type']);
            $content = $this->getValue();
        } else {
            $value = $this->getValue();
            if ($value !== null) {
                $tagAttributes['value'] = $value;
            } elseif ($this->hasDefaultValue()) {
                $tagAttributes['value'] = $this->getDefaultValue();
            }
        }
        $tag = HtmlAttributes::buildTag($tagName, $tagAttributes, $content);
        if ($wrapper) {
            $tag = HtmlAttributes::buildTag($wrapper, $attributes, $tag);
        }
        return $tag;
    }
}
