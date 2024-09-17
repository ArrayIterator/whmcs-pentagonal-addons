import "../scss/pentagonal.scss";

((w: Window) : void => {
    const {
        document : d
    } = w;
    // run app
    const run = () : void => {
        const baseURL = new URL(w.location.href);
        const pentagonal = d.getElementById('pentagonal-addon-section');
        const pentagonal_definition_uri = w["pentagonal_definition_uri" as any] as unknown as {
            addon_name : string;
            addon_url : string;
            addons_url : string;
            admin_url : string;
            base_url : string;
            theme_url : string;
            templates_url : string;
            asset_url : string;
            module_url : string;
        };
        if (!pentagonal || !pentagonal_definition_uri || typeof pentagonal_definition_uri !== 'object') {
            return;
        }
        const addonName = pentagonal_definition_uri.addon_name;
        if (!addonName || baseURL.searchParams.get('module') !== addonName) {
            return;
        }
        d.body.setAttribute('data-pentagonal-loaded', 'true');
        console.debug('5-GoNAL');
        const nav = d.querySelector('body > .navigation');
        const footer = d.querySelector('body > .footerbar');
        if (nav && footer) {
            const setupSpaceHeight = (): void => {
                const navHeight = nav.clientHeight + footer.clientHeight;
                d.body.attributeStyleMap.set('--pentagonal-content-space-height', navHeight + 'px');
            };
            w.addEventListener('resize', setupSpaceHeight);
            setupSpaceHeight();
        }
        setTimeout(() => {
            pentagonal?.classList.remove('pentagonal-addon-wait');
        }, 1000);
    }
    ['complete', 'interactive'].includes(d.readyState) ? run() : w.addEventListener('DOMContentLoaded', run);
})(window);
