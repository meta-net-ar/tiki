import { execSync } from "child_process";
import { fixtureCommand } from "./fixtures";

// Runs once before the whole test session (wired via playwright.config.ts → globalSetup).
// Seeds the shared prerequisites that every suite assumes but that no per-TC fixture owns:
//   1. Test1 / Test2 registered users (create_test_users.php)
//   2. the "User Encryption" security feature enabled in the DB (enable_encryption.php)
//
// Best-effort by design: a misconfigured FIXTURE_CMD (or a run with no live Tiki, e.g.
// the harness-only smoke/tc00 spec) must not abort the session. On failure we print a
// loud, actionable warning and let the affected suites fail with their own clear errors.

type Step = { label: string; script: string };

const STEPS: Step[] = [
    { label: "test users", script: "create_test_users.php" },
    { label: "encryption feature", script: "enable_encryption.php" },
];

export default function globalSetup(): void {
    for (const { label, script } of STEPS) {
        const cmd = fixtureCommand(script);
        try {
            const out = execSync(cmd, { stdio: "pipe" }).toString().trim();
            console.log(`[e2e setup] ${label}: ${out || "ok"}`);
        } catch (err) {
            const detail = err instanceof Error ? err.message : String(err);
            console.warn(
                `\n[e2e setup] WARNING — could not seed ${label}.\n` +
                    `  Command: ${cmd}\n` +
                    `  Reason:  ${detail.split("\n")[0]}\n` +
                    `  Suites that depend on it may fail. Fix TIKI_FIXTURE_CMD in tests/e2e/.env,\n` +
                    `  or run the command above manually from the Tiki root.\n`
            );
        }
    }
}
