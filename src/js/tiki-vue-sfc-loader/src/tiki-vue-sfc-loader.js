import { loadModule } from "vue3-sfc-loader";

/**
 * Mounts a dynamically loaded Vue component to a specific DOM element.
 * @param {string} targetId - The ID of the DOM element to mount the component.
 * @param {string} componentUrl - The URL of the `.vue` component.
 * @param {object} [propsData={}] - The props data to pass to the component.
 * @param {string} mode - The mode to load Vue in ('development' or 'production').
 */
export async function mountApp(targetId, componentUrl, propsData = {}, mode = "development") {
    $(`#${targetId}`).html($.IMPORT_LOADER_MARKUP);

    const Vue = await initVue(mode);

    const app = Vue.createApp({
        render() {
            return Vue.h(
                Vue.defineAsyncComponent(() => loadModule(componentUrl, getOptions(Vue))),
                {
                    ...propsData,
                }
            );
        },
    });

    app.mount(`#${targetId}`);
}

function initVue(mode = "development") {
    const moduleName = mode === "production" ? "vue-prod" : "vue-dev";
    return import(moduleName);
}

function getOptions(Vue) {
    return {
        moduleCache: {
            vue: Vue,
        },
        async getFile(url) {
            const res = await fetch(url);
            if (!res.ok) {
                throw Object.assign(new Error(res.statusText + " " + url), { res });
            }
            return {
                getContentData: (asBinary) => (asBinary ? res.arrayBuffer() : res.text()),
            };
        },
        addStyle(textContent) {
            const style = Object.assign(document.createElement("style"), { textContent });
            const ref = document.head.getElementsByTagName("style")[0] || null;
            document.head.insertBefore(style, ref);
        },
    };
}
