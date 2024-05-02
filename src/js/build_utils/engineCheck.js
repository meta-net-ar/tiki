import checkEngines from "check-engines";

checkEngines((err) => {
    if (err) {
        console.error(err);
        process.exit(1);
    }
});
