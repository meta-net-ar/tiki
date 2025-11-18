// import singleSpaVue from "single-spa-vue";
// import singleSpaCss from "single-spa-css";
// import { createApp, h } from "vue";
// import App from "./App.vue";

// import "../custom.scss";

// const vueLifecycles = singleSpaVue({
//     createApp,
//     appOptions: {
//         render() {
//             return h(App, {
//                 // single-spa props are available on the "this" object. Forward them to your component as needed.
//                 // https://single-spa.js.org/docs/building-applications#lifecyle-props
//                 // name: this.name,
//                 // mountParcel: this.mountParcel,
//                 // singleSpa: this.singleSpa,
//                 durationData: this.durationData,
//             });
//         },
//     },
//     handleInstance: (app) => {
//         if (import.meta.env.MODE === "development") {
//             console.log(import.meta.env);
//         }
//     },
// });

// const cssLifecycle = singleSpaCss({
//     cssUrls: [
//         {
//             href: "public/generated/js/duration-picker.css",
//         },
//     ],
// });

// export const bootstrap = [cssLifecycle.bootstrap, vueLifecycles.bootstrap];
// export const mount = [cssLifecycle.mount, vueLifecycles.mount];
// export const unmount = [cssLifecycle.unmount, vueLifecycles.unmount];
// export const DurationPicker = App;

import { createApp, h } from "vue";
import App from "./App.vue";
import "../custom.scss";

class DurationPickerApp {
    constructor() {
        this.app = null;
        this.loaded = false;
    }

    // Load CSS (replaces singleSpaCss)
    async loadCSS() {
        return new Promise((resolve) => {
            if (document.querySelector('link[href="public/generated/js/duration-picker.css"]')) {
                resolve();
                return;
            }

            const link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = "public/generated/js/duration-picker.css";
            link.onload = resolve;
            link.onerror = resolve; // Continue even if CSS fails
            document.head.appendChild(link);
        });
    }

    // Bootstrap (replaces single-spa bootstrap)
    async bootstrap() {
        if (this.loaded) return;

        await this.loadCSS();
        this.loaded = true;
    }

    // Mount (replaces single-spa mount)
    async mount(container, props = {}) {
        await this.bootstrap();

        this.app = createApp({
            render() {
                return h(App, {
                    durationData: props.durationData,
                });
            },
        });

        // Development logging
        if (import.meta.env.MODE === "development") {
            console.log("DurationPicker mounted with props:", props);
            console.log(import.meta.env);
        }

        this.app.mount(container);
        return this.app;
    }

    // Unmount (replaces single-spa unmount)
    async unmount() {
        if (this.app) {
            this.app.unmount();
            this.app = null;
        }
    }
}

// Create singleton instance
const durationPicker = new DurationPickerApp();

// Export for direct use
export const mount = durationPicker.mount.bind(durationPicker);
export const unmount = durationPicker.unmount.bind(durationPicker);
export const bootstrap = durationPicker.bootstrap.bind(durationPicker);

// Export component for manual use
export const DurationPicker = App;
// export default durationPicker;
