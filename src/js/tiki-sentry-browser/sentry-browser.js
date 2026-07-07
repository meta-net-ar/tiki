/**
 * Sentry Browser module for Tiki
 * Exports Sentry SDK and initialization function
 */
import * as Sentry from "@sentry/browser";
export { Sentry };

/**
 * Initialize Sentry with the provided configuration
 * @param {Object} config - Configuration from PHP
 * @param {string} config.dsn - Sentry DSN
 * @param {number} config.sampleRate - Error sample rate
 * @param {boolean} config.tracingEnabled - Whether tracing is enabled
 * @param {number} config.tracesSampleRate - Tracing sample rate
 * @returns {Object} Initialized Sentry instance
 */
export function initSentry(config) {
    try {
        // Build init configuration
        const initConfig = {
            dsn: config.dsn,
            sampleRate: config.sampleRate,
        };

        // Add tracing if enabled
        if (config.tracingEnabled) {
            initConfig.integrations = [
                Sentry.browserTracingIntegration({
                    tracePropagationTargets: [location.origin],
                    instrumentPageLoad: true,
                    instrumentNavigation: true,
                }),
            ];
            initConfig.tracesSampleRate = config.tracesSampleRate;
        }

        Sentry.init(initConfig);

        return Sentry;
    } catch (e) {
        console.error("Failed to initialize Sentry:", e); // eslint-disable-line no-console
        throw e;
    }
}
