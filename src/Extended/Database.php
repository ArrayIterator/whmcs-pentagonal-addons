<?php
/** @noinspection PhpComposerExtensionStubsInspection */
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Extended;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractExtended;

/**
 * Class to handle the database facade
 *
 * @mixin \WHMCS\Database
 *
 * @method static \WHMCS\Database getFacadeApplication()
 * @method static \WHMCS\Database facadeApplication()
 * @method static \WHMCS\Database getAccessor()
 * @method static \WHMCS\Database accessor()
 * @method static \PDO getPdo()
 */
class Database extends AbstractExtended
{
    /**
     * @inheritDoc
     * @return \WHMCS\Route\UriPath
     */
    protected function getFacadeApplication(): \WHMCS\Database
    {
        return parent::getFacadeApplication();
    }

    /**
     * @inheritDoc
     */
    protected function getFacadeName(): string
    {
        return 'db';
    }
}
