import singleSpaVue from "single-spa-vue";
import singleSpaCss from "single-spa-css";
import { createApp, h } from "vue";
import App from "./App.vue";

import "../custom.scss";

const vueLifecycles = singleSpaVue({
    createApp,
    appOptions: {
        render() {
            return h(App, {
                // single-spa props are available on the "this" object. Forward them to your component as needed.
                // https://single-spa.js.org/docs/building-applications#lifecyle-props
                // name: this.name,
                // mountParcel: this.mountParcel,
                // singleSpa: this.singleSpa,
                durationData: this.durationData,
            });
        },
    },
    handleInstance: (app) => {
        if (import.meta.env.MODE === "development") {
            console.log(import.meta.env);
        }
    },
});

const cssLifecycle = singleSpaCss({
    cssUrls: [
        {
            href: "public/generated/js/duration-picker.css",
        },
    ],
});

export const bootstrap = [cssLifecycle.bootstrap, vueLifecycles.bootstrap];
export const mount = [cssLifecycle.mount, vueLifecycles.mount];
export const unmount = [cssLifecycle.unmount, vueLifecycles.unmount];
export const DurationPicker = App;
