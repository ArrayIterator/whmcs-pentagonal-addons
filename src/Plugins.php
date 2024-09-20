<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon;

use Pentagonal\Neon\WHMCS\Addon\Schema\SchemaModel\PluginSchema;
use function dirname;

/**
 * Class Plugins for Neon WHMCS Addon
 */
class Plugins
{
    /**
     * @var Core $core
     */
    protected Core $core;

    /**
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        $this->core = $core;
        $this
            ->getCore()
            ->getSchemas()
            ->get(PluginSchema::class)
            ->addPluginsDirectory(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'plugins');
    }

    /**
     * @return Core
     */
    public function getCore(): Core
    {
        return $this->core;
    }
}
