import fs from 'fs';

//If the _custom dir doesn't exist, the build will fail trying to watch a non-existent directory
let p = "{__dirname}/../../../_custom";
fs.mkdirSync(p, { recursive: true, mode: 0o774 });
