<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Extended;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractExtended;
use Pentagonal\Neon\WHMCS\Addon\Extended\Traits\LanguageTrait;
use WHMCS\Language\AdminLanguage as WHMCSLanguage;
use function call_user_func_array;

/**
 * Class to handle the admin language
 *
 * @mixin WHMCSLanguage
 *
 * @method static WHMCSLanguage getFacadeApplication()
 * @method static WHMCSLanguage facadeApplication()
 * @method static WHMCSLanguage getAccessor()
 * @method static WHMCSLanguage accessor()
 * @method static string lang(string $key, string $domain = 'messages')
 * @method static string translate(string $key, string $domain = 'messages')
 * @method static WHMCSLanguage catalogue()
 * @method static WHMCSLanguage getCatalogue(string $locale = null)
 */
final class AdminLanguage extends AbstractExtended
{
    use LanguageTrait;

    /**
     * @inheritDoc
     */
    protected function getFacadeName(): string
    {
        return 'adminlang';
    }

    /**
     * @nheritDoc
     */
    protected function magicCaller(string $name, array $arguments)
    {
        $this->initialize();
        switch ($name) {
            case 'getcatalogue':
                return $this->getFacadeApplication()->getCatalogue(...$arguments);
            case 'catalogue':
                return $this->getFacadeApplication()->getCatalogue();
            case 'lang':
            case 'translate':
                return $this->getFacadeApplication()->getCatalogue()->get(...$arguments);
        }
        return call_user_func_array([$this->getFacadeApplication(), $name], $arguments);
    }

    /**
     * @inheritDoc
     */
    protected function getFacadeApplication(): WHMCSLanguage
    {
        return parent::getFacadeApplication();
    }
}
