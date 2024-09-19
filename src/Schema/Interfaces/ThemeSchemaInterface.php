<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces;

interface ThemeSchemaInterface extends JsonSchemaInterface
{
    public const REF = "https://hub.pentagonal.org/schemas/whmcs/addons/pentagonal/theme.json";

    /**
     * Get the schema source keyof : $schema
     *
     * @return string
     */
    public function getSchemaSource() : string;

    /**
     * Get the name of the theme
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the version of the theme
     *
     * @return ?string
     */
    public function getVersion(): ?string;

    /**
     * Get the description of the theme
     *
     * @return ?string
     */
    public function getDescription(): ?string;

    /**
     * Get the url of the theme
     *
     * @return ?string
     */
    public function getUrl(): ?string;

    /**
     * Get the author of the theme
     *
     * @return ?string
     */
    public function getAuthor(): ?string;

    /**
     * Get the author url of the theme
     *
     * @return ?string
     */
    public function getAuthorUrl(): ?string;

    /**
     * Get the license of the theme
     *
     * @return ?string
     */
    public function getLicense(): ?string;

    /**
     * Get the license url of the theme
     *
     * @return ?string
     */
    public function getLicenseUrl(): ?string;

    /**
     * Get the thumbnail of the theme
     *
     * @return ?string
     */
    public function getThumbnail(): ?string;

    /**
     * Get the language directory of the theme
     *
     * @return string
     */
    public function getLanguageDirectory(): string;

    /**
     * Get the language file of the theme
     *
     * @return bool
     */
    public function isHooksEnabled(): bool;

    /**
     * Full path to the hooks file
     * if the hooks disabled return null
     *
     * @return ?string
     */
    public function getHooksFile() : ?string;

    /**
     * Get the language file of the theme
     *
     * @return bool
     */
    public function isServiceEnabled(): bool;

    /**
     * Full path to the service file
     * if the service disabled return null
     *
     * @return ?string
     */
    public function getServiceFile() : ?string;
}
