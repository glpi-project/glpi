
module.exports = {
    "extends": "stylelint-config-standard-scss",
    "ignoreFiles": [
        "**/*.!(scss)",
        "css/legacy/**/*",
        "css/lib/**/*",
    ],
    "rules": {
        // DISABLED pure naming rules, no functional impact
        "scss/at-function-pattern": null, // DISABLE Expected function name to be kebab-case
        "scss/dollar-variable-pattern": null, // DISABLE: Expected variable to be kebab-case
        "selector-class-pattern": null, // DISABLE: Expected class selector to be kebab-case
        "selector-id-pattern": null, // DISABLE: Expected id selector to be kebab-case

        // DISABLED pure coding style rules, no functional impact
        "color-function-notation": null, // DISABLE: Expected modern color-function notation
        "declaration-block-no-redundant-longhand-properties": null, // DISABLE Expected shorthand property "flex-flow"
        "media-feature-range-notation": "prefix",
        "scss/at-rule-conditional-no-parentheses": null,
        "scss/no-global-function-names": null, // scssphp do not support usage of SASS modules

        // ALTERED/DISABLED code quality rules, may have a functional impact, override should be removed
        "font-family-no-missing-generic-family-keyword": [
            true,
            {
                "ignoreFontFamilies": [
                    "Font Awesome 6 Free",
                    "tabler-icons",
                ],
            }
        ],
        "no-descending-specificity": null, // DISABLE: Expected selector ".a" to come before selector ".b .a"
        "no-duplicate-selectors": null, // DISABLE: Unexpected duplicate selector ".a", first used at line XXX

        // DISABLED code validity rules, override MUST be removed ASAP
        "no-invalid-position-at-import-rule": null,
    },
};
