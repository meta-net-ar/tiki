import { existsSync } from "fs";
import { resolve, dirname } from "path";
import { fileURLToPath } from "url";
import { defineConfig, devices } from "@playwright/test";

const __dirname = dirname(fileURLToPath(import.meta.url));

// Auto-load .env when present; vars already set in the environment are never overridden.
// Uses Node.js 20.12+ built-in — no dotenv dependency needed.
const dotEnvFile = resolve(__dirname, ".env");
if (existsSync(dotEnvFile)) (process as any).loadEnvFile(dotEnvFile);

// Self-contained mode (TIKI_E2E_WEBSERVER=1): Playwright starts PHP's built-in
// web server on this checkout and the tests target it — no pre-existing Tiki
// instance or external web server needed. Provision the dedicated test database
// first with bin/e2e-provision.sh. In this mode TIKI_BASE_URL is ignored: the
// spawned server is by definition the test target.
const SELF_CONTAINED = process.env.TIKI_E2E_WEBSERVER === "1";
const E2E_PORT = Number(process.env.TIKI_E2E_PORT ?? 8686);
const TIKI_ROOT = resolve(__dirname, "../..");

// Playwright resolves leading-slash paths against the URL origin, not the full baseURL path.
// Split TIKI_BASE_URL into origin (for Playwright) + pathname (for the custom page fixture).
// The fixture in common/test.ts reads TIKI_BASE_PATH and prepends it to every goto/request call.
const rawBaseURL = SELF_CONTAINED
    ? `http://127.0.0.1:${E2E_PORT}`
    : (process.env.TIKI_BASE_URL ?? "http://localhost");
// Normalize the env var so specs that read TIKI_BASE_URL (e.g. smoke/tc00)
// always see the effective target, including in self-contained mode.
process.env.TIKI_BASE_URL = rawBaseURL;
const parsedURL = new URL(rawBaseURL);
if (process.env.TIKI_BASE_PATH === undefined) {
    process.env.TIKI_BASE_PATH = parsedURL.pathname.replace(/\/$/, "");
}
const baseURL = parsedURL.origin;

export default defineConfig({
    testDir: "./suites",
    // Seeds test users + enables the User Encryption feature once before the session.
    globalSetup: "./common/global-setup",
    timeout: 60_000,
    expect: { timeout: 10_000 },
    fullyParallel: false, // Tiki tests share DB state — run serially
    workers: 1, // Single worker enforces strict serial order across all spec files
    retries: 0,
    reporter: [
        ["list"],
        ["html", { outputFolder: "playwright-report", open: "never" }],
    ],
    use: {
        baseURL,
        ignoreHTTPSErrors: true, // local dev instances often use self-signed certificates
        trace: "on",
        screenshot: "only-on-failure",
        video: "retain-on-failure",
    },
    projects: [{ name: "chromium", use: { ...devices["Desktop Chrome"] } }],
    // Self-contained mode: serve this checkout with PHP's built-in server.
    // The suite only requests direct .php URLs, which the built-in server
    // executes without needing Apache rewrite rules. Workers > 1 let the
    // browser fetch assets in parallel.
    // The server logs every request (3 lines each) plus PHP errors to stderr,
    // which Playwright would otherwise pipe into the test output — a full CI
    // run produces ~4MB of [WebServer] lines and blows GitLab's job log limit.
    // Redirect stderr to a file instead; CI uploads it as an artifact.
    webServer: SELF_CONTAINED
        ? {
              command: `php -S 127.0.0.1:${E2E_PORT} 2> tests/e2e/php-server.log`,
              cwd: TIKI_ROOT,
              url: `http://127.0.0.1:${E2E_PORT}/tiki-login.php`,
              reuseExistingServer: !process.env.CI,
              timeout: 30_000,
              env: { PHP_CLI_SERVER_WORKERS: "4" },
          }
        : undefined,
});
