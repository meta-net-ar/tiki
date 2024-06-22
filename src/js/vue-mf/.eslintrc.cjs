module.exports = {
    root: true,
    env: {
        node: true,
        browser: true,
    },
    extends: [
        "eslint:recommended",
        "plugin:vue/vue3-recommended",
        "plugin:prettier/recommended"
    ],
    plugins: ["prettier"],
    rules: {
        indent: ["error", 4],
        "vue/no-unused-vars": "off", //vue/no-unused-vars does not support args: none
        "no-unused-vars": ["error", { args: "none" }],
        "vue/component-name-in-template-casing": ["error", "PascalCase"],
    },
};
