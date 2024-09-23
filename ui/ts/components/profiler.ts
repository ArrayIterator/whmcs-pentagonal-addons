import {AddonUriDefinition, ComponentCallback} from "../types/definitions";
import DebugBar from "./templates/DebugBar";
import {ReactElement} from "react";
import {renderToStaticMarkup} from "react-dom/server";
export default ((w: Window, arg: AddonUriDefinition) => {
    const doc = w.document;
    const jsonDebugBar = doc.querySelector('script[type="application/json"][id=pentagonal-performance-debug-bar-profiler]');
    if (!jsonDebugBar) {
        return;
    }
    let element: ReactElement;
    try {
        element = DebugBar({
            addonUri: arg,
            jsonDebugBar: JSON.parse(jsonDebugBar.innerHTML)
        });
    } catch (e) {
        return;
    } finally {
        jsonDebugBar.remove();
    }
    const section = doc.getElementById('pentagonal-addon-section');
    if (!section) {
        element = null;
        return;
    }
    const debugBar = doc.createElement('div');
    debugBar.className = 'pentagonal-debug-bar flex fixed';
    debugBar.innerHTML = renderToStaticMarkup(element);
    section.appendChild(debugBar);

    const openCommand = debugBar.querySelector('.action-up');
    const closeCommand = debugBar.querySelector('.action-down');
    const maximizeCommand = debugBar.querySelector('.action-maximize');
    const minimizeCommand = debugBar.querySelector('.action-minimize');
    closeCommand.addEventListener('click', function () {
        changeStatus('closed');
    });
    maximizeCommand.addEventListener('click', function () {
        changeStatus('maximized');
    });
    openCommand.addEventListener('click', function () {
        changeStatus('opened');
    });
    minimizeCommand.addEventListener('click', function () {
        changeStatus('opened');
    });

    const resizeClass = 'pentagonal-debug-bar-resizing';
    let headerHeight = debugBar.querySelector('.pentagonal-debug-header').getBoundingClientRect().height;
    const isAllowResize = function () {
        return debugBar.getAttribute('data-status') === 'opened';
    };
    let bounding,
        posNow;
    let isResizing = false,
        offsetTop = 0,
        yetResizing = false;
    debugBar.addEventListener("mousedown", function (e) {
        isResizing = isAllowResize() && e.offsetY <= 5 && e.offsetY >= -5;
    });
    const changeStatus = function (status: string) {
        debugBar.setAttribute('data-status', status);
        debugBar.removeAttribute('style');
        offsetTop = 0;
        isResizing = false;
        if (status === 'closed') {
            // debugBar.querySelectorAll(selectorHasInfo).forEach(function (e) {
            //     let info = e.parentNode.querySelector(selectorInfoSection);
            //     if (!info) {
            //         return;
            //     }
            //     e.classList.remove(activeStatus);
            //     info.classList.remove(activeStatus);
            // });
            section.style.marginBottom = headerHeight + 'px';
        } else if (status === 'opened') {
            section.style.marginBottom = (window.getComputedStyle(debugBar).height||250) + 'px';
        } else {
            section.style.marginBottom = status === 'maximized'? '100vh' : window.getComputedStyle(debugBar).height + 'px';
        }
    };
    changeStatus('closed');
    // changeStatus('maximized');
    doc.addEventListener('mousemove', function (e) {
        // we don't want to do anything if we aren't resizing.
        if (!isResizing) {
            return;
        }
        if (!debugBar.classList.contains(resizeClass)) {
            debugBar.classList.add(resizeClass);
        }
        bounding = debugBar.getBoundingClientRect();
        posNow = (e.clientY - bounding.top);
        offsetTop = bounding.height - posNow;
        if (offsetTop < headerHeight) {
            return;
        }
        yetResizing = true;
        debugBar.style.height = offsetTop + 'px';
        section.style.marginBottom = offsetTop + 'px';
    });
    doc.addEventListener('mousemove', function (e) {
        // we don't want to do anything if we aren't resizing.
        if (!isResizing) {
            return;
        }
        if (!debugBar.classList.contains(resizeClass)) {
            debugBar.classList.add(resizeClass);
        }
        bounding = debugBar.getBoundingClientRect();
        posNow = (e.clientY - bounding.top);
        offsetTop = bounding.height - posNow;
        if (offsetTop < headerHeight) {
            return;
        }
        yetResizing = true;
        debugBar.style.height = offsetTop + 'px';
        section.style.marginBottom = offsetTop + 'px';
    });
    doc.addEventListener('mouseup', function () {
        // stop resizing
        debugBar.classList.remove(resizeClass);
        if (!isResizing || ! yetResizing) {
            yetResizing = false;
            isResizing = false;
            return;
        }
        yetResizing = false;
        isResizing = false;
        if (offsetTop <= headerHeight) {
            changeStatus('closed');
        } else if (window.innerHeight <= offsetTop) {
            changeStatus('maximized');
        }
    });
    debugBar.querySelectorAll('.pentagonal-debug-record > .pentagonal-debug-record-log').forEach(function (e: HTMLDivElement) {
        e.addEventListener('click', function () {
            e.parentElement.classList.toggle('active');
        });
    });

    debugBar.querySelector('input[type="search"]')
        ?.addEventListener('keyup', function (e) {
            const search = e.target as HTMLInputElement;
            const value = search.value.trim().toLowerCase();
            debugBar.querySelectorAll('.pentagonal-debug-record').forEach(function (e: HTMLDivElement) {
                const text = e.querySelector('.pentagonal-debug-record-log').textContent;
                if (value && text.toLowerCase().indexOf(value) === -1) {
                    const data = e.querySelector('.pentagonal-debug-record-data').textContent;
                    if (data.toLowerCase().indexOf(value) === -1) {
                        e.classList.add('hidden');
                        e.classList.remove('active');
                        return;
                    } else {
                        e.classList.add('active');
                        e.classList.remove('hidden');
                        return;
                    }
                }
                e.classList.remove('hidden');
                e.classList.remove('active');
            });
    });
}) as ComponentCallback;
