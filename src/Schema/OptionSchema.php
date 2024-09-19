<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Schema;

use Pentagonal\Neon\WHMCS\Addon\Schema\Interfaces\OptionSchemaInterface;
use Pentagonal\Neon\WHMCS\Addon\Schema\Traits\SchemaThemeConstructorTrait;
use function file_exists;
use function realpath;
use const DIRECTORY_SEPARATOR;

class OptionSchema implements OptionSchemaInterface
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
     * Get the schema source keyof : $schema
     *
     * @return string
     */
    public function getSchemaSource() : string
    {
        return $this->get('$schema')??self::REF;
    }

    /**
     * @InheritDoc
     */
    public function getSchemaFile(): string
    {
        return $this->schemaFile ??= $this->getThemeDir()
            . DIRECTORY_SEPARATOR
            . 'schema'
            . DIRECTORY_SEPARATOR
            . 'options.json';
    }

    /**
     * @return string
     */
    public function getRefSchemaFile(): string
    {
        $file = __DIR__ .'/SchemaFiles/options.json';
        return $this->refSchemaFile ??= file_exists($file)
            ? (realpath($file)?:$file)
            :  self::REF;
    }
}
