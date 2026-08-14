import { execSync } from "child_process";

let php;
try {
    const phpPaths = process.platform === "win32" ? execSync("where php").toString().trim() : execSync("which php").toString().trim();
    php = phpPaths.split("\n")[0];
} catch (error) {
    if (error.status !== 0) {
        console.error("Unable to find PHP", error.message); // eslint-disable-line no-console
        console.error("This is a temporary workaround for CI environment. If you are seeing this building a real Tiki your Tiki will NOT work properly!"); // eslint-disable-line no-console
        process.exit(0);
    }
}
try {
    execSync(`${php.trim()} console.php build:generateiconlist`, { stdio: "inherit" });
} catch (error) {
    if (error.status !== 0) {
        console.error("An error occurred while trying to execute the PHP command:", error.message); // eslint-disable-line no-console
        process.exitCode = error.status;
    }
}