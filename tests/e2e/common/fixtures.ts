import { execSync } from "child_process";
import { existsSync } from "fs";
import { basename, dirname, resolve } from "path";
import { fileURLToPath } from "url";
import type { TestType } from "@playwright/test";

const __dirname = dirname(fileURLToPath(import.meta.url));

// Command used to invoke PHP fixtures (setup/teardown scripts).
// Override with TIKI_FIXTURE_CMD to match your environment. Examples:
//   DDEV (auto-detected):  ddev exec php /var/www/html[/.worktrees/<name>]
//   Plain PHP:             php /absolute/path/to/tiki
//   Docker:                docker exec my-container php /var/www/html
//
// Auto-detection: resolves the Tiki root from this file's location (4 levels up),
// detects git worktrees via "/.worktrees/<name>" in the path, then checks for a
// .ddev directory in the main project root to decide between ddev exec and plain php.
function buildDefaultFixtureCmd(): string {
    const tikiRootOnHost = resolve(__dirname, "../../..");

    // Detect git worktree: path ends with /.worktrees/<name>
    const worktreeMatch = tikiRootOnHost.match(/\.worktrees\/([^/]+)$/);

    // Main project root is where .ddev/ lives (strip worktree suffix if present)
    const mainProjectRoot = worktreeMatch
        ? tikiRootOnHost.replace(/\/\.worktrees\/[^/]+$/, "")
        : tikiRootOnHost;

    if (existsSync(resolve(mainProjectRoot, ".ddev"))) {
        // DDEV project detected — PHP runs inside the container
        const containerPath = worktreeMatch
            ? `/var/www/html/.worktrees/${worktreeMatch[1]}`
            : "/var/www/html";
        return `ddev exec php ${containerPath}`;
    }

    // No DDEV — plain PHP with the absolute host path
    return `php ${tikiRootOnHost}`;
}

// Self-contained mode (TIKI_E2E_WEBSERVER=1) serves THIS checkout via php -S,
// so fixtures must run with the host PHP against this checkout. It deliberately
// overrides TIKI_FIXTURE_CMD: honoring a ddev/docker command from .env here
// would mutate a different instance than the one under test.
export const FIXTURE_CMD =
    process.env.TIKI_E2E_WEBSERVER === "1"
        ? `php ${resolve(__dirname, "../../..")}`
        : (process.env.TIKI_FIXTURE_CMD ?? buildDefaultFixtureCmd());

// Every PHP fixture runs through fixtures/bootstrap.php, which owns the CLI guard, the
// Tiki bootstrap, admin permissions and the JSON in/out contract, so the fixture files
// themselves hold only their own logic. Script paths are relative to fixtures/, which
// keeps callers from having to know where the Tiki root sits inside a container.
const BOOTSTRAP_SCRIPT = "tests/e2e/fixtures/bootstrap.php";

/**
 * Build the shell command that runs one PHP fixture through the bootstrap.
 *
 * @param script Path relative to tests/e2e/fixtures/, e.g. "shared-secrets/tc04_setup.php"
 * @param input  Optional payload for the fixture, passed base64-encoded so it survives
 *               `ddev exec` / docker / plain php quoting.
 */
export function fixtureCommand(script: string, input?: unknown): string {
    const arg =
        input === undefined
            ? ""
            : ` ${Buffer.from(JSON.stringify(input)).toString("base64")}`;
    return `${FIXTURE_CMD}/${BOOTSTRAP_SCRIPT} ${script}${arg}`;
}

// Subpath prefix extracted from TIKI_BASE_URL by playwright.config.ts.
// Empty string for root installs (DDEV, bare localhost) — no-op in that case.
export const BASE_PATH: string = process.env.TIKI_BASE_PATH ?? "";

// ── Convention-based suite fixtures (system-wide setup/teardown) ─────────────

export interface SuiteFixtures {
    /** Parsed JSON printed by tcNN_setup.php ({} until setup has run). */
    data: Record<string, any>;
    /** True once setup ran successfully. */
    loaded: boolean;
}

/**
 * Wire the PHP setup/teardown fixtures for a spec file by naming convention.
 *
 * Call once at the top of any spec:
 *
 *     const fx = useSuiteFixtures(test);
 *
 * For a spec at suites/<suite>/tcNN-*.spec.ts the runner looks for
 *     fixtures/<suite>/tcNN_setup.php     → run in beforeAll
 *     fixtures/<suite>/tcNN_teardown.php  → run in afterAll
 * Scripts that do not exist are skipped silently, so the call is a no-op
 * for specs without fixtures.
 *
 * Contract:
 *  - setup prints a single JSON object on stdout → exposed as `fx.data`;
 *  - teardown receives that same JSON back as `$input`, handed over
 *    base64-encoded so it is shell-safe through `ddev exec` / docker / plain php;
 *  - if setup fails, the whole spec file is skipped with a warning
 *    pointing at TIKI_FIXTURE_CMD.
 */
export function useSuiteFixtures(test: TestType<any, any>): SuiteFixtures {
    const fx: SuiteFixtures = { data: {}, loaded: false };

    test.beforeAll(async ({}, testInfo) => {
        const scripts = resolveFixtureScripts(testInfo.file);
        if (!scripts || !scripts.setupExists) {
            return;
        }
        try {
            const out = execSync(fixtureCommand(scripts.setupRel), {
                stdio: "pipe",
            })
                .toString()
                .trim();
            fx.data = JSON.parse(out);
            fx.loaded = true;
        } catch (err) {
            const msg = err instanceof Error ? err.message : String(err);
            // execSync errors keep the child's output in .stdout/.stderr, not
            // in .message — without them a CI log only says "Command failed".
            const { stdout, stderr } = err as { stdout?: Buffer; stderr?: Buffer };
            const output = [
                stderr?.toString().trim() && `  stderr: ${stderr.toString().trim()}`,
                stdout?.toString().trim() && `  stdout: ${stdout.toString().trim()}`,
            ]
                .filter(Boolean)
                .join("\n");
            console.warn(
                `[${scripts.label}] Fixture setup failed — skipping suite.\n` +
                    `  FIXTURE_CMD=${FIXTURE_CMD}\n  Error: ${msg}` +
                    (output ? `\n${output}` : "")
            );
            test.skip(
                true,
                `${scripts.label} fixture setup failed — check TIKI_FIXTURE_CMD`
            );
        }
    });

    test.afterAll(async ({}, testInfo) => {
        const scripts = resolveFixtureScripts(testInfo.file);
        if (!scripts || !scripts.teardownExists || !fx.loaded) {
            return;
        }
        try {
            execSync(fixtureCommand(scripts.teardownRel, fx.data), {
                stdio: "pipe",
            });
        } catch (_) {
            console.warn(
                `[${scripts.label}] Teardown fixture failed — manual cleanup may be needed`
            );
        }
    });

    return fx;
}

// Derive fixture script paths from a spec file path, or null when the spec
// does not follow the tcNN-*.spec.ts naming convention.
function resolveFixtureScripts(specFile: string) {
    const tcMatch = basename(specFile).match(/^(tc\d+)/i);
    if (!tcMatch) {
        return null;
    }
    const tc = tcMatch[1].toLowerCase();
    const suite = basename(dirname(specFile));
    const e2eRoot = resolve(__dirname, "..");
    const setupRel = `${suite}/${tc}_setup.php`;
    const teardownRel = `${suite}/${tc}_teardown.php`;
    return {
        label: tc.replace(/^tc/, "TC-").toUpperCase(),
        setupRel,
        teardownRel,
        setupExists: existsSync(
            resolve(e2eRoot, "fixtures", suite, `${tc}_setup.php`)
        ),
        teardownExists: existsSync(
            resolve(e2eRoot, "fixtures", suite, `${tc}_teardown.php`)
        ),
    };
}
