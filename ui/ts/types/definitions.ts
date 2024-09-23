export type AddonUriDefinition = {
    addon_name : string;
    addon_url : string;
    addons_url : string;
    admin_url : string;
    base_url : string;
    theme_url : string;
    templates_url : string;
    asset_url : string;
    module_url : string;
}
export type ComponentCallback = (window: Window, arg: AddonUriDefinition) => any;
