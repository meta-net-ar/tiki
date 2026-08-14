// This PostCSS plugin replaces all instances of :root with :host in CSS files that are part of the Vue widgets.
const plugin = () => {
    return {
        postcssPlugin: "postcss-root-to-host",
        Rule(rule) {
            if (rule.source.input.file.includes("/vue-widgets/")) {
                if (rule.selector.includes(":root") && !rule.selector.includes(":host")) {
                    rule.selector = rule.selector.replace(":root", ":host");
                }
            }
        },
    };
};

plugin.postcss = true;

export default plugin;
