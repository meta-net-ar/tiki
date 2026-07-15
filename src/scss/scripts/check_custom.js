import fs from "fs";

//If the _custom dir doesn't exist, the build will fail trying to watch a non-existent directory

//This used to be _custom, but in the root _custom is a symlink, the build also failed.  So we create and target the directories than can contain themes (and thus SCSS), which also saves on file watchers.
let paths = ["{__dirname}/../../../_custom/shared/themes", "{__dirname}/../../../_custom/sites"];

for (const path of paths) {
    fs.mkdirSync(path, { recursive: true, mode: 0o774 });
}
