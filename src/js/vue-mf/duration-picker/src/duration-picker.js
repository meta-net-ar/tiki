import { createApp } from "vue";
import App from "./App.vue";

import "../custom.scss";

let vueApp = null;

function createVueApp(props = {}) {
    if (vueApp) {
        return vueApp;
    }

    vueApp = createApp(App, props);
    
    if (import.meta.env.MODE === "development") {
        console.log(import.meta.env);
    }
    
    return vueApp;
}

function mount(container, props = {}) {
    const app = createVueApp(props);
    app.mount(container);
    return app;
}

function unmount() {
    if (vueApp) {
        vueApp.unmount();
        vueApp = null;
    }
}

export const bootstrap = () => Promise.resolve();
export const mountApp = mount;
export const unmountApp = unmount;
export const DurationPicker = App;
