<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Extended\Traits;

use Pentagonal\Neon\WHMCS\Addon\Core;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;
use Throwable;
use function file_exists;
use function is_string;
use function method_exists;

trait LanguageTrait
{
    private $initialized = false;

    private function initialize()
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
            $directory = Core::factory()->getAddon()->getAddonDirectory() . '/languages';
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
