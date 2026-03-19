import { createApp } from "vue";
import App from "./App.vue";

import "../custom.scss";

const mountedApps = new Map();

function createVueApp(props = {}) {
    const app = createApp(App, props);

    if (import.meta.env.MODE === "development") {
        console.log(import.meta.env);
    }

    return app;
}

function mount(container, props = {}) {
    const app = createVueApp(props);
    app.mount(container);
    mountedApps.set(container, app);
    return app;
}

function unmount(container) {
    if (container && mountedApps.has(container)) {
        const app = mountedApps.get(container);
        app.unmount();
        mountedApps.delete(container);
    }
}

export const bootstrap = () => Promise.resolve();
export const mountApp = mount;
export const unmountApp = unmount;
export const DurationPicker = App;
