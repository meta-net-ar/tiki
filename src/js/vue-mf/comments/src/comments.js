import singleSpaVue from "single-spa-vue";
import singleSpaCss from "single-spa-css";
import { createApp, h } from "vue";
import App from "./App.vue";
import { createPinia } from "pinia";
import "../custom.scss";

const vueLifecycles = singleSpaVue({
    createApp,
    appOptions: {
        render() {
            return h(App, {
                // single-spa props are available on the "this" object. Forward them to your component as needed.
                // https://single-spa.js.org/docs/building-applications#lifecyle-props
                customProps: {
                    commentsContext: this.commentsContext,
                },
            });
        },
    },
    handleInstance: (app) => {
        app.use(createPinia());
    },
});

const cssLifecycle = singleSpaCss({
    cssUrls: [
        {
            href: "public/generated/js/comments.css",
        },
    ],
});

export const bootstrap = [cssLifecycle.bootstrap, vueLifecycles.bootstrap];
export const mount = [cssLifecycle.mount, vueLifecycles.mount];
export const unmount = [cssLifecycle.unmount, vueLifecycles.unmount];
