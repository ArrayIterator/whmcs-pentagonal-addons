<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema;

use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\ThemeSchemaInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Traits\SchemaThemeConstructorTrait;
use function file_exists;
use function is_bool;
use function realpath;
use const DIRECTORY_SEPARATOR;

class ThemeSchema implements ThemeSchemaInterface
{
    use SchemaThemeConstructorTrait;

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
    public function getSchemaSource(): string
    {
        return $this->get('$schema')??self::REF;
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
    public function getVersion(): ?string
    {
        return $this->get('version');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return $this->get('description');
    }

    /**
     * @inheritdoc
     */
    public function getUrl(): ?string
    {
        return $this->get('url');
    }

    /**
     * @inheritdoc
     */
    public function getAuthor(): ?string
    {
        return $this->get('author');
    }

    /**
     * @inheritdoc
     */
    public function getAuthorUrl(): ?string
    {
        return $this->get('author_url');
    }

    /**
     * @inheritdoc
     */
    public function getLicense(): ?string
    {
        return $this->get('license');
    }

    /**
     * @inheritdoc
     */
    public function getLicenseUrl(): ?string
    {
        return $this->get('license_url');
    }

    /**
     * @inheritdoc
     */
    public function getThumbnail(): ?string
    {
        return $this->get('thumbnail');
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
     * @inheritDoc
     */
    public function getRefSchemaFile(): string
    {
        $file = __DIR__ .'/SchemaFiles/theme.json';
        return $this->refSchemaFile ??= file_exists($file)
            ? (realpath($file)?:$file)
            :  self::REF;
    }
}
