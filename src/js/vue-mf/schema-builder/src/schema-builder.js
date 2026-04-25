import singleSpaVue from "single-spa-vue";
import singleSpaCss from "single-spa-css";
import { createApp, h } from "vue";
import App from "./App.vue";
import "../style.scss";

//createApp(App).mount("#app");

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
                trackerfieldMap: this.trackerfieldMap,
                schemajson: this.schemajson,
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
            href: "public/generated/js/tiki-schema-builder.css",
        },
    ],
});

export const bootstrap = [
    cssLifecycle.bootstrap,
    vueLifecycles.bootstrap,
    async function () {
        console.log("Bootstrap complete for schema builder");
    },
];
export const mount = [
    cssLifecycle.mount, 
    vueLifecycles.mount,
    async function () {
        console.log("Mount complete for schema builder");
    },
];
export const unmount = [
    cssLifecycle.unmount,
    vueLifecycles.unmount,
    async function () {
        console.log("Unmount complete for schema builder");
    },
];
export const SchemaBuilder = App;
