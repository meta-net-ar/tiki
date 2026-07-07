import singleSpaVue from "single-spa-vue";
import singleSpaCss from "single-spa-css";
import { createApp, h } from "vue";
import App from "./App.vue";

import "ui-predicate-vue3/dist/ui-predicate-vue3.css";
import "../custom.scss";
import UIPredicate from "ui-predicate-vue3";

const vueLifecycles = singleSpaVue({
    createApp,
    appOptions: {
        render() {
            return h(App, {
                customProps: {
                    // single-spa props are available on the "this" object. Forward them to your component as needed.
                    // https://single-spa.js.org/docs/building-applications#lifecyle-props
                    name: this.name,
                    mountParcel: this.mountParcel,
                    singleSpa: this.singleSpa,
                    trackerRulesObject: this.trackerRulesObject,
                },
            });
        },
    },
    handleInstance: (app) => {
        app.config.idPrefix = "tracker-rules";
        app.use(UIPredicate);
        if (import.meta.env.MODE === "development") {
            // eslint-disable-next-line no-console
            console.log(import.meta.env);
        }
    },
});

const cssLifecycle = singleSpaCss({
    cssUrls: [
        {
            href: "public/generated/js/tracker-rules.css",
        },
        {
            href: "public/generated/js/element-plus-ui/input-root-css.css",
        },
        {
            href: "public/generated/js/element-plus-ui/datepicker-root-css.css",
        },
        {
            href: "public/generated/js/element-plus-ui/select-root-css.css",
        },
    ],
});

export const bootstrap = [cssLifecycle.bootstrap, vueLifecycles.bootstrap];
export const mount = [cssLifecycle.mount, vueLifecycles.mount];
export const unmount = [cssLifecycle.unmount, vueLifecycles.unmount];
export const TrackerRules = App;
