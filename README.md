# PENTAGONAL ADDONS FOR WHMCS

Pentagonal Addons is the advanced level for WHMCS.
It can make additional level of whmcs rule

## Features
- Autoload the hooks on active templates
- Autoload the service on active templates

```txt
templates/
    └── template-name/
        ├── schema/
        │   └── theme.json (the theme schema)
        ├── hooks/
        │   └── hooks.php (the hooks file)
        └── services/
            └── services.php (the services file)
```

Autoload the hooks and services on active templates should be placed on the `hooks` and `services` directory and should declare on schema/theme.json with
`hooks: true` and `services: true`

See [options+themes.json](schema/options+themes.json) for the schema structure

### Example

> Hooks

`hooks.php` - see the [https://developers.whmcs.com/hooks/hook-index/](https://developers.whmcs.com/hooks/hook-index/) 

For list of available hooks

```php
<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Theme;

use Pentagonal\Neon\WHMCS\Addon\Services\Hooks;

if (!isset($hooks) || !$hooks instanceof Hooks) {
    return;
}

if (!defined('WHMCS')) {
    header('Location: ../../', true, 301);
    exit();
}

$dynamicHooks = $hooks->createDynamicHook(
    'ClientAreaPage',
    function ($vars) {
        // do in vars
        return $vars;
    },
    10,
    'ClientAreaPage'
);
// queue the hooks
$hooks->queue($dynamicHooks);

```

> Services

`services.php` - the service file, load before `hooks.php` loaded

```php
<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Theme;

use Pentagonal\Neon\WHMCS\Addon\Abstracts\AbstractService;
use Pentagonal\Neon\WHMCS\Addon\Interfaces\RunnableServiceInterface;
use Pentagonal\Neon\WHMCS\Addon\Libraries\Services;

if (!isset($services) || !$services instanceof Services) {
    return;
}

if (!defined('WHMCS')) {
    header('Location: ../../', true, 301);
    exit();
}

class ExampleService extends AbstractService implements RunnableServiceInterface
{
    /**
     * @inheritDoc
     */
    protected function dispatch(...$args)
    {
        // @todo do the service dispatch
    }
}

$services->add(ExampleService::class);

```
## Generating WHMCS Stubs

To generate WHMCS stubs, you can use the following command:

```bash
php bin/gen_stub.php --whmcs-dir /path/to/whmcs
```

> Accepted Arguments

- `--whmcs-dir=[whmcs-path]` - whmcs installation directory 
- `-q` or `--quiet` to quiet (without value)
- `-f` or `--force` to continue without interactive (without value)
- `-v` or `-vv` or `-vvv` the verbose level (without value)


## Requirements

Please make sure the WHMCS already installed and properly configured.

And the `php-cli` for execution already configure with ioncube loader installed.
