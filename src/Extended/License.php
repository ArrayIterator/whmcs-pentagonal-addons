<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Extended;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractExtended;

/**
 * Class to handle the license facade
 *
 * @mixin \WHMCS\License
 *
 * @method static \WHMCS\License getFacadeApplication()
 * @method static \WHMCS\License facadeApplication()
 * @method static \WHMCS\License getAccessor()
 * @method static \WHMCS\License accessor()
 */
class License extends AbstractExtended
{
    public const LICENSE_API_VERSION = \WHMCS\License::LICENSE_API_VERSION;

    public const LICENSE_API_HOSTS = \WHMCS\License::LICENSE_API_HOSTS;

    public const STAGING_LICENSE_API_HOSTS = \WHMCS\License::STAGING_LICENSE_API_HOSTS;

    public const UNLICENSED_KEY = \WHMCS\License::UNLICENSED_KEY;

    /**
     * @inheritDoc
     * @return \WHMCS\License
     */
    protected function getFacadeApplication(): \WHMCS\License
    {
        return parent::getFacadeApplication();
    }

    /**
     * @inheritDoc
     */
    protected function getFacadeName(): string
    {
        return 'license';
    }
}
