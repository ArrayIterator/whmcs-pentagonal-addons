<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema\SchemaModel;

use Pentagonal\Hub\Schema;
use Pentagonal\Hub\Schema\Whmcs\Theme;
use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\ThemeSchemaInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Traits\SchemaThemeTrait;
use Swaggest\JsonSchema\Structure\ObjectItemContract;
use function is_bool;
use const DIRECTORY_SEPARATOR;

class ThemeSchema implements ThemeSchemaInterface
{
    use SchemaThemeTrait {
        SchemaThemeTrait::getSchema as private getSchemaTrait;
    }

    /**
     * @var string $schemaFile the schema file
     */
    protected string $schemaFile;

    /**
     * @var string $refSchemaFile the reference schema file
     */
    protected string $refSchemaFile;

    /**
     * @inheritDoc
     */
    public function getSchemaClassName(): string
    {
        return Theme::class;
    }

    /**
     * @inheritDoc
     */
    public function getRefSchema(): ?ObjectItemContract
    {
        return $this->refSchema ??= Schema::createSchemaReference(Theme::class);
    }

    /**
     * @return ?Theme
     */
    public function getSchema(): ?Theme
    {
        $schema = $this->getSchemaTrait();
        if ($schema instanceof Theme) {
            return $schema;
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSchemaSource(): string
    {
        return $this->get('$schema')??Schema::determineInternalSchemaURL(Theme::class);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->get('name')??'';
    }

    /**
     * @inheritdoc
     */
    public function getVersion(): string
    {
        return $this->get('version')??'';
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return $this->get('description')??"";
    }

    /**
     * @inheritdoc
     */
    public function getUrl(): string
    {
        return $this->get('url')??'';
    }

    /**
     * @inheritdoc
     */
    public function getAuthor(): string
    {
        return $this->get('author')??'';
    }

    /**
     * @inheritdoc
     */
    public function getAuthorUrl(): string
    {
        return $this->get('author_url')??'';
    }

    /**
     * @inheritdoc
     */
    public function getLicense(): string
    {
        return $this->get('license')??'';
    }

    /**
     * @inheritdoc
     */
    public function getLicenseUrl(): string
    {
        return $this->get('license_url')??"";
    }

    /**
     * @inheritdoc
     */
    public function getLanguageDirectory(): string
    {
        return $this->get('language_directory')??'languages';
    }

    /**
     * @inheritdoc
     */
    public function getDate(): ?string
    {
        return $this->get('date')??'';
    }

    /**
     * @inheritdoc
     */
    public function getUpdated(): ?string
    {
        return $this->get('updated')??'';
    }

    /**
     * @inheritdoc
     */
    public function isTranslate(): bool
    {
        return (bool) $this->get('translate');
    }

    /**
     * @inheritdoc
     */
    public function getChangelog(): array
    {
        return $this->get('changelog')??[];
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(): array
    {
        return (array) ($this->get('metadata')??[]);
    }

    /**
     * @inheritdoc
     */
    public function isHooksEnabled(): bool
    {
        $enabled = $this->get('hooks');
        return is_bool($enabled) ? $enabled : false;
    }

    /**
     * @inheritdoc
     */
    public function getHooksFile(): ?string
    {
        if ($this->isHooksEnabled()) {
            return $this->getThemeDir() . '/hooks/hooks.php';
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function isServiceEnabled(): bool
    {
        $enabled = $this->get('services');
        return is_bool($enabled) ? $enabled : false;
    }

    /**
     * @inheritdoc
     */
    public function getServiceFile(): ?string
    {
        if ($this->isServiceEnabled()) {
            return $this->getThemeDir() . '/services/services.php';
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSchemaFile(): string
    {
        return $this->schemaFile ??= $this->getThemeDir()
            . DIRECTORY_SEPARATOR
            . 'schema'
            . DIRECTORY_SEPARATOR
            . 'theme.json';
    }

    /**
     * Get settings
     * @return array
     */
    public function getSettings() : array
    {
        return $this->get('settings')??[];
    }

    /**
     * @inheritDoc
     */
    public function getRefSchemaFile(): string
    {
        return $this->refSchemaFile ??= Schema::determineInternalSchemaURL(Theme::class);
    }
}
