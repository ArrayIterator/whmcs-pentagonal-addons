<link id="pentagonal-addon-app-css" rel="stylesheet" media="all" href="{addon_url path="/assets/css/runtime.css"}?v={$addon_version}">
<link id="pentagonal-addon-app-css" rel="stylesheet" media="all" href="{addon_url path="/assets/css/pentagonal.css"}?v={$addon_version}">
<script id="pentagonal-addon-app-js" async defer type="text/javascript" src="{addon_url path="/assets/js/pentagonal.js"}?v={$addon_version}"></script>
<script id="pentagonal-addon-runtime-js" async defer type="text/javascript" src="{addon_url path="/assets/js/runtime.js"}?v={$addon_version}"></script>
{if ($pre_load_output|is_string)}
    {$pre_load_output}
{/if}