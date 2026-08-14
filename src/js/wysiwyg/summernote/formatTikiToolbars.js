import admintoolbar from "./actions/admintoolbar";
import languagecheck from "./actions/languagecheck";
import replace from "./actions/replace";
import tikilink from "./actions/tikilink";
import createCustomButton from "./createCustomButton";

const CUSTOM_TOOLS = [
    "specialChar",
    "replace",
    "emoji",
    "tikiimage",
    "tikifile",
    "tikilink",
    "tikiswitch",
    "tiki_launchplugins",
    "linkfile",
    "autosave",
    "admintoolbar",
    "languagecheck",
];
export const CUSTOM_ACTIONS = {
    replace,
    tikilink,
    admintoolbar,
    languagecheck,
};

export default function (toolbar) {
    const flattenDepth1 = toolbar.flat(1);
    const flatten = [];
    const lastGroupsIndexes = [];
    flattenDepth1.forEach((item, index) => {
        const lastSeparator = item.lastIndexOf("-");
        if (index > 0) flatten.push("-");
        lastGroupsIndexes.push([flatten.length + 1 + lastSeparator, item.length - lastSeparator - 1]);
        flatten.push(...item);
    });

    const tools = {};
    const icons = {};
    const customButtons = {};

    let groupIndex = 0;

    let latestGroupIndexCount = 0;
    flatten.forEach((item, index) => {
        if (item === "-") {
            groupIndex++;
            return;
        }
        let group = `group-${groupIndex}`;
        const lastGroupIndex = lastGroupsIndexes.find(([lastIndex]) => lastIndex === index);

        if (lastGroupIndex || (tools["last-" + groupIndex] && tools["last-" + groupIndex].length < latestGroupIndexCount)) {
            latestGroupIndexCount = lastGroupIndex ? lastGroupIndex[1] : latestGroupIndexCount;
            group = "last-" + groupIndex;
        }
        if (!tools[group]) {
            tools[group] = [];
        }
        tools[group].push(item.token);
        if (item.icon) {
            icons[item.token] = item.icon;
        }
        if (item.type === "Wikiplugin" || CUSTOM_TOOLS.includes(item.token)) {
            if (CUSTOM_ACTIONS[item.token]) {
                item.callback = CUSTOM_ACTIONS[item.token];
            }

            customButtons[item.token] = createCustomButton(item);
        }
    });

    return {
        tools: Object.keys(tools)
            .sort((a, b) => a.split("-")[0].localeCompare(b.split("-")[0]))
            .map((group) => [group, tools[group]]),
        icons,
        customButtons,
    };
}
