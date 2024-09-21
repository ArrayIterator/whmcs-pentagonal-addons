<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Extended\Traits;

use Pentagonal\Neon\WHMCS\Addon\Singleton;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;
use Throwable;
use function dirname;
use function file_exists;
use function is_dir;
use function is_string;
use function method_exists;

trait LanguageTrait
{
    private bool $initialized = false;

    /**
     * Initialize
     *
     * @return void
     */
    private function initialize() : void
    {
        if ($this->initialized || !method_exists($this, 'getFacadeApplication')) {
            return;
        }
        $app = $this->getFacadeApplication();
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!$app instanceof Translator) {
            return;
        }
        $this->initialized = true;
        try {
            /** @noinspection PhpInternalEntityUsedInspection */
            $fallbackLocale = $app->getFallbackLocales();
            $app->addLoader(PhpFileLoader::class, new PhpFileLoader());
            $core = Singleton::core();
            if ($core) {
                $directory = $core->getAddon()->getAddonDirectory();
            } else {
                $directory = dirname(__DIR__, 3);
            }
            $directory .= '/languages';
            if (!is_dir($directory)) {
                return;
            }
            foreach ($fallbackLocale as $locale) {
                if (!is_string($locale)) {
                    continue;
                }
                $file = $directory . '/' . $locale . '.php';
                if (file_exists($file)) {
                    $app->addResource(PhpFileLoader::class, $file, $locale);
                }
            }
        } catch (Throwable $e) {
            // do nothing
        }
    }
}
