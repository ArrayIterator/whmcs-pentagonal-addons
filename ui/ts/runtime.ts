import grapesjs from "grapesjs";
import sortablejs from "sortablejs";
import "../scss/runtime.scss";
import {VERSION} from "./constant";

(function (w: Window) {
    const runtime : {
        version: string;
        grapesjs : typeof grapesjs;
        sortablejs : typeof sortablejs;
    } = {
        version: VERSION,
        grapesjs,
        sortablejs
    }

    Object.freeze(runtime);
    const pentagonal_runtime = 'pentagonal_runtime';
    const descriptor = Object.getOwnPropertyDescriptor(w, pentagonal_runtime);
    if (!descriptor || descriptor.writable) {
        Object.defineProperty(w, pentagonal_runtime, {
            value: runtime,
            configurable: false,
            writable: false
        });
    }
})(window);
