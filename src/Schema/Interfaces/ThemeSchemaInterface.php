<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces;

use Pentagonal\Hub\Schema\Whmcs\Theme;

interface ThemeSchemaInterface extends SingleSchemaInterface
{
    /**
     * @return ?Theme
     */
    public function getSchema(): ?Theme;

    /**
     * Get the schema source keyof : $schema
     *
     * @return string
     */
    public function getSchemaSource() : string;

    /**
     * Get theme name
     *
     * @return ?string
     */
    public function getName(): string;

    /**
     * Get theme version
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Get theme url
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor(): string;

    /**
     * Get author url
     *
     * @return string
     */
    public function getAuthorUrl(): string;

    /**
     * Get license
     *
     * @return string
     */
    public function getLicense(): string;

    /**
     * Get license url
     *
     * @return string
     */
    public function getLicenseUrl(): string;

    /**
     * Get date
     *
     * @return ?string return null if not set
     */
    public function getDate(): ?string;

    /**
     * Get updated
     *
     * @return ?string return null if not set
     */
    public function getUpdated(): ?string;

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get language directory
     *
     * @return string
     */
    public function getLanguageDirectory(): string;

    /**
     * Get translate
     *
     * @return bool
     */
    public function isTranslate(): bool;

    /**
     * Get changelog
     *
     * @return array
     */
    public function getChangelog(): array;

    /**
     * Get metadata
     *
     * @return array
     */
    public function getMetadata() : array;

    /**
     * Get settings
     *
     * @return array|object
     */
    public function getSettings();

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
