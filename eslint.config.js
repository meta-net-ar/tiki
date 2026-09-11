import stylistic from "@stylistic/eslint-plugin";
import pluginVue from "eslint-plugin-vue";
import eslintPluginPrettierRecommended from "eslint-plugin-prettier/recommended";
export default [
    //https://eslint.vuejs.org/user-guide/
    //...pluginVue.configs["flat/essential"],
    {
        ignores: [
            // These use https://www.npmjs.com/package/minimatch syntax
            // Ignore minified files anywhere
            "**/*.min.js",
            "**/*-min.js",

            // Ignore included libraries
            "vendor/**",
            "vendor_bundled/**",
            "vendor_custom/**",

            // Ignore site.js installed by composer from included library
            "lib/cypht/site.js",
            "lib/openlayers/**",
            "src/js/vue-mf/tracker-rules/src/lib/**",

            // Ignore Generated Files
            "public/generated/**",
            "temp/**",
            ".gitlab-ci-local/**",

            // Ignore Playwright test artifacts (gitignored, not present in CI)
            "tests/e2e/playwright-report/**",
            "tests/e2e/test-results/**",
            "tests/e2e/node_modules/**",
        ],
    },
    {
        files: ["*.vue"],
        plugins: {
            vue: pluginVue,
        },
        rules: {
            indent: "off",
            "vue/script-indent": ["error", 4],
            "vue/no-unused-vars": "off", //vue/no-unused-vars does not support args: none
            "no-unused-vars": ["error", { args: "none" }],
            "vue/component-name-in-template-casing": ["error", "PascalCase"],
        },
    },
    {
        ...eslintPluginPrettierRecommended,
        files: ["src/js/**/*.js"],
    },
    {
        plugins: {
            "@stylistic": stylistic,
        },
        rules: {
            "@stylistic/no-trailing-spaces": "error",
            "@stylistic/linebreak-style": ["error", "unix"],
            "@stylistic/semi": ["error", "always"],
            "no-console": "error",
        },
        languageOptions: {
            ecmaVersion: 11,
            sourceType: "module",
            //browser: true,
            //jquery: true,
        },
    },
];
