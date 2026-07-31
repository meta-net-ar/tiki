/**
 * Single-spa app registration guard module.
 *
 * This module provides a function `registerSingleSpaApp(config)` that wraps
 * single-spa's `registerApplication` with a guard to prevent duplicate registrations.
 * It uses a shared global function `ensureSingleSpaRegistered(appName, registerFn)`
 * to make the registration idempotent across multiple invocations.
 *
 */
(function () {
    if (window.__singleSpaRegistrationModule) return;
    window.__singleSpaRegistrationModule = true;

    // // Fallback: prevent duplicate single-spa app registrations when shared guard is missing
    if (typeof window.ensureSingleSpaRegistered !== "function") {
        window.ensureSingleSpaRegistered = function (appName, registerFn) {
            window.__spaRegistered = window.__spaRegistered || new Set();
            if (window.__spaRegistered.has(appName)) {
                console.debug("[spa-guard-fallback] already registered:", appName);
                return;
            }
            window.__spaRegistered.add(appName);
            try {
                registerFn();
            } catch (err) {
                window.__spaRegistered.delete(appName);
                throw err;
            }
        };
    }

    /**
     * Register a single-spa microfrontend application with idempotent guard.
     *
     * @param config object: the expected config shape `name`, `app`, `activeWhen`, `customProps` maps to single-spa registration fields.
     *  - `name` (string):   Unique identifier for the microfrontend. Used by single-spa to track registration and lifecycle.
     *  - `app` (object | function):
     *      * an object that implements the lifecycle methods `{ bootstrap, mount, unmount, update? }` (each returns a Promise), or
     *      * a loader function that returns a Promise resolving to that object, e.g. `() => import('./my-app')` or `() => System.import('my-app')`.
     *       This lets you lazy-load the app bundle.
     *  - `activeWhen` (string | function | array): Controls when the app should be active:
     *      * a path prefix string (e.g. `"/dashboard"`) — commonly used with helpers like `singleSpaPathPrefix('/app')`,
     *      * a function `(location) => boolean` that receives `window.location` and returns true when the app should mount,
     *      * or an array of either strings or functions for multiple conditions.
     *  - `customProps` (object): Arbitrary props passed to the app lifecycles.
     *
     **/

    function registerSingleSpaApp(config) {
        if (!config || typeof config !== "object") {
            console.error("[single-spa-register] invalid config", config);
            return;
        }
        var name = config.name || config.appName || config.app?.name;
        if (!name) {
            console.error("[single-spa-register] missing app name in config", config);
            return;
        }
        if (typeof window.registerApplication !== "function") {
            console.error("[single-spa-register] single-spa registerApplication is not available for", name);
            return;
        }

        // Use the shared guard to make registration idempotent
        window.ensureSingleSpaRegistered(name, function () {
            try {
                // expected shape: { name, app, activeWhen, customProps }
                window.registerApplication(name, config.app, config.activeWhen, config.customProps || {});
                console.debug("[single-spa-register] registered:", name);
            } catch (err) {
                console.error("[single-spa-register] failed to register", name, err);
                throw err;
            }
        });
    }
    window.registerSingleSpaApp = registerSingleSpaApp;
})();
