import { createApp, h } from "vue";
import App from "./App.vue";
import "../custom.scss";
import ElementPlus from "element-plus";
import en from "element-plus/dist/locale/en.mjs";

export function mount(container, props = {}) {
    const app = createApp({
        render() {
            return h(App, {
                durationData: props.durationData,
            });
        },
    });

    if (import.meta.env.MODE === "development") {
        console.log("DurationPicker mounted");
    }

    app.mount(container);

    app.use(ElementPlus, {
        locale: en,
    });

    return app;
}

export function unmount(app) {
    if (app) {
        app.unmount();
    }
}

export const DurationPicker = App;
