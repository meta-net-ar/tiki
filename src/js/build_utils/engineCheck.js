import { fileURLToPath } from "url";
import { dirname, resolve } from "path";
import checkEngine from "check-engine";

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const pkgPath = resolve(__dirname, "../../../package.json");

checkEngine(pkgPath).then((result) => {
    if (result.status !== 0) {
        console.log(result);
        process.exit(1);
    } else {
        console.log("Engine checked!");
    }
});
