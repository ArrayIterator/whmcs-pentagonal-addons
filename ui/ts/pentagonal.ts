import "../scss/pentagonal.scss";
import {AddonUriDefinition, ComponentCallback} from "./types/definitions";
import profiler from "./components/profiler";
((w: Window) : void => {
    const {
        document : d
    } = w;

    const components : Array<ComponentCallback> = [
        profiler
    ];

    // run app
    const run = () : void => {
        const baseURL = new URL(w.location.href);
        const pentagonal = d.getElementById('pentagonal-addon-section');
        const pentagonal_definition_uri = w["pentagonal_definition_uri" as any] as unknown as AddonUriDefinition;
        if (!pentagonal || !pentagonal_definition_uri || typeof pentagonal_definition_uri !== 'object') {
            return;
        }
        const addonName = pentagonal_definition_uri.addon_name;
        if (!addonName || baseURL.searchParams.get('module') !== addonName) {
            return;
        }
        d.body.setAttribute('data-pentagonal-loaded', 'true');
        console.debug('5-GoNAL Loaded');
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
            setTimeout(() => {
                d.querySelector('.pentagonal-addon-wait-loader')?.remove();
            }, 1000);
        }, 1000);

        // call
        while (components.length) {
            try {
                components.shift()(w, pentagonal_definition_uri);
            } catch(e) {
                // pass
            }
        }
    }

    ['complete', 'interactive'].includes(d.readyState) ? run() : w.addEventListener('DOMContentLoaded', run);
})(window);
