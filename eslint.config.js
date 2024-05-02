import stylisticJs from "@stylistic/eslint-plugin-js";
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
            "lib/vue/lib/**",

            // Ignore Generated Files
            "public/generated/**",
            "temp/**",
            ".gitlab-ci-local/**"
        ]
    },
    {
        files: ["*.vue"],
        plugins: {
            vue: pluginVue
        },
        rules: {
            indent: "off",
            "vue/script-indent": ["error", 4],
            "vue/no-unused-vars": "off", //vue/no-unused-vars does not support args: none
            "no-unused-vars": ["error", { args: "none" }],
            "vue/component-name-in-template-casing": ["error", "PascalCase"]
        }
    },
    {
        ...eslintPluginPrettierRecommended,
        files: ["src/js/**/*.js"]
    },
    {
        plugins: {
            "@stylistic/js": stylisticJs
        },
        rules: {
            "@stylistic/js/no-trailing-spaces": "error",
            "@stylistic/js/linebreak-style": ["error", "unix"],
            "@stylistic/js/semi": ["error", "always"]
        },
        languageOptions: {
            ecmaVersion: 11,
            sourceType: "module"
            //browser: true,
            //jquery: true,
        }
    }
];
