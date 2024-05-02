import singleSpaVue from "single-spa-vue";
//import { cssLifecycle } from "vite-plugin-single-spa/ex";
import singleSpaCss from "single-spa-css";

import { createApp, h } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
//import { SetupCalendar, DatePicker } from "v-calendar";
import Toast from "vue-toastification";
import { defineRule } from "vee-validate";
import App from "./App.vue";
import store from "./store";
import autosize from "autosize";
//import Vue3ColorPicker from "vue3-colorpicker";
import "vue3-perfect-scrollbar/style.css";
import "vue-toastification/dist/index.css";
//import "vue3-colorpicker/style.css";

import "../custom.scss";

const vueLifecycles = singleSpaVue({
    createApp,
    appOptions: {
        render() {
            return h(App, {
                customProps: {
                    // single-spa props are available on the "this" object. Forward them to your component as needed.
                    // https://single-spa.js.org/docs/building-applications#lifecyle-props
                    // name: this.name,
                    // mountParcel: this.mountParcel,
                    // singleSpa: this.singleSpa,
                    kanbanData: this.kanbanData,
                },
            });
        },
    },
    handleInstance: (app) => {
        // Register a global custom directive called `v-focus`
        app.directive("focus", {
            // When the bound element is mounted into the DOM...
            mounted(el) {
                // Focus the element
                el.focus();
            },
        });
        app.directive("autosize", {
            // When the bound element is mounted into the DOM...
            mounted(el) {
                // Focus the element
                if (el.tagName === "TEXTAREAsdfsdfsd") autosize(el);
            },
        });
        app.use(store);
        app.use(PerfectScrollbar);
        //app.use(SetupCalendar, {}).component("DatePicker", DatePicker);
        app.use(Toast, {
            timeout: 4000,
            hideProgressBar: true,
            showCloseButtonOnHover: true,
            icon: false,
        });
        //app.use(Vue3ColorPicker);
        if (import.meta.env.MODE === "development") {
            console.log(import.meta.env);
        }
    },
});

defineRule("minLength", (value, [limit]) => {
    if (value.length < parseInt(limit)) {
        return `This field must be at least ${limit} character`;
    }
    return true;
});

const cssLifecycle = singleSpaCss({
    cssUrls: [
        {
            href: "public/generated/js/kanban.css",
        },
    ],
});

export const bootstrap = [cssLifecycle.bootstrap, vueLifecycles.bootstrap];
export const mount = [cssLifecycle.mount, vueLifecycles.mount];
export const unmount = [cssLifecycle.unmount, vueLifecycles.unmount];
