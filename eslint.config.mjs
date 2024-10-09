import mocha from "eslint-plugin-mocha";
import globals from "globals";
import vue from "eslint-plugin-vue";
// import path from "node:path";
// import { fileURLToPath } from "node:url";
import js from "@eslint/js";
import cypress from "eslint-plugin-cypress/flat";
// import { FlatCompat } from "@eslint/eslintrc";
//
// const __filename = fileURLToPath(import.meta.url);
// const __dirname = path.dirname(__filename);
// const compat = new FlatCompat({
//     baseDirectory: __dirname,
//     recommendedConfig: js.configs.recommended,
//     allConfig: js.configs.all,
//     vueConfig: vue.configs.essential
// });

export default [
    {
        // 'ignores' without other keys apparently acts as global ignores...
        ignores: [
            "config/*",
            "files/*",
            "lib/*",
            "marketplace/*",
            "node_modules/*",
            "plugins/*",
            "public/lib/*",
            "public/build/*",
            "tests/config/*",
            "vendor/*",
            "**/*.min.js"
        ],
    },
    js.configs.recommended,
    {
        languageOptions: {
            ecmaVersion: 13,
            globals: {
                ...globals.browser,
                ...globals.jquery,
                ...globals.es2021,
                ...{
                    CFG_GLPI: true,
                    tinyMCE: true,
                    __: true,
                    _n: true,
                    _x: true,
                    _nx: true
                }
            }
        },
        rules: {
            "eol-last": [
                "error",
                "always"
            ],
            "indent": [
                "error",
                4,
                {
                    "SwitchCase": 1
                }
            ],
            "linebreak-style": [
                "error",
                "unix"
            ],
            "no-console": [
                "error",
                {
                    "allow": [
                        "warn",
                        "error"
                    ]
                }
            ],
            "no-unused-vars": [
                "error",
                {
                    "vars": "local"
                }
            ],
            "quotes": [
                "off",
                "single"
            ],
            "semi": [
                "error",
                "always"
            ],
            "no-var": "error",
            "prefer-arrow-callback": "error",
            "no-eval": "error",
            "no-implied-eval": "error",
            "prefer-const": "error",
            "prefer-spread": "error",
            "prefer-template": "error",
        }
    },
    {
        // Modules
        files: ["js/modules/**"],
        languageOptions: {
            sourceType: "module"
        }
    },
    ...vue.configs["flat/essential"],
    {
        // Vue
        files: ["js/src/**", "tests/js/**"],
        plugins: {vue},
        languageOptions: {
            globals: {...globals.node},
            sourceType: "module"
        },
        rules: {
            "vue/script-indent": ["error", 4, {
                "baseIndent": 1,
                "switchCase": 1
            }],
            "vue/html-indent": ["error", 4, {
                "baseIndent": 1,
                "switchCase": 1
            }],
            "vue/multi-word-component-names": "off",
            "indent": "off"
        }
    },
    {
        // Jest Tests
        files: ["tests/js/**"],
        plugins: {mocha},
        languageOptions: {
            globals: {...globals.node, ...globals.jest},
            sourceType: "module",
        },
        rules: {
            "mocha/no-skipped-tests": "error",
            "mocha/no-exclusive-tests": "error"
        }
    },
    {
        // Cypress Tests
        ...cypress.configs.recommended,
        files: ["tests/cypress/**"],
        plugins: {mocha},
        languageOptions: {
            globals: {...globals.node, ...globals.jest, ...cypress.configs.globals.languageOptions.globals},
            sourceType: "module",
        },
        rules: {
            "mocha/no-skipped-tests": "error",
            "mocha/no-exclusive-tests": "error"
        }
    },
    {
        // Config files
        "files": [".stylelintrc.js", ".webpack.config.js", "tests/cypress.config.js"],
        "languageOptions": {
            "globals": {...globals.node}
        }
    }
];
