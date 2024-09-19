<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Converter\Interfaces;

use Pentagonal\Neon\WHMCS\Addon\Schema\Converter\SettingConverter;
use Stringable;
use Swaggest\JsonSchema\Structure\ObjectItemContract;

interface TagInterface extends Stringable
{
    /**
     * TagInterface constructor.
     *
     * @param SettingConverter $converter
     * @param ObjectItemContract $contract
     * @param ?TagInterface $parent
     */
    public function __construct(
        SettingConverter $converter,
        ObjectItemContract $contract,
        ?TagInterface $parent = null
    );

    /**
     * Get the converter
     *
     * @return SettingConverter
     */
    public function getConverter(): SettingConverter;

    /**
     * Check if the tag is valid
     *
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Get the contract
     *
     * @return ObjectItemContract
     */
    public function getContract(): ObjectItemContract;

    /**
     * Get parent tag
     *
     * @return ?TagInterface
     */
    public function getParent(): ?TagInterface;

    /**
     * Get the tag label
     *
     * @return ?string
     */
    public function getLabel() : ?string;

    /**
     * Get the tag title
     * @return ?string
     */
    public function getTitle() : ?string;

    /**
     * Get tag description
     *
     * @return ?string
     */
    public function getDescription() : ?string;

    /**
     * Get the tag name
     * @return string
     */
    public function getTagName(): string;

    /**
     * @return array<string, mixed>
     */
    public function getTagAttributes(): array;

    /**
     * To string
     *
     * @param string|null $wrapper [optional] <div> wrapper if no wrapper is provided it will return the tag only
     * @param array $attributes
     * @return string
     */
    public function toHtml(?string $wrapper = null, array $attributes = []): string;
}
