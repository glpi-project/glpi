/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

module.exports = {
    "extends": "stylelint-config-standard-scss",
    "ignoreFiles": [
        "**/*.!(scss)",
        "css/legacy/**/*",
        "css/lib/**/*",
    ],
    "rules": {
        "indentation": 4,
        "max-line-length": null,

        // DISABLED pure naming rules, no functionnal impact
        "scss/at-function-pattern": null, // DISABLE Expected function name to be kebab-case
        "scss/dollar-variable-pattern": null, // DISABLE: Expected variable to be kebab-case
        "selector-class-pattern": null, // DISABLE: Expected class selector to be kebab-case
        "selector-id-pattern": null, // DISABLE: Expected id selector to be kebab-case

        // DISABLED pure coding style rules, no functionnal impact
        "color-function-notation": null, // DISABLE: Expected modern color-function notation
        "declaration-block-no-redundant-longhand-properties": null, // DISABLE Expected shorthand property "flex-flow"
        "scss/at-rule-conditional-no-parentheses": null,
        "scss/no-global-function-names": null, // scssphp do not support usage of SASS modules

        // ALTERED/DISABLED code quality rules, may have a functionnal impact, override should be removed
        "font-family-no-missing-generic-family-keyword": [
            true,
            {
                "ignoreFontFamilies": [
                    "Font Awesome 5 Free",
                ],
            }
        ],
        "no-descending-specificity": null, // DISABLE: Expected selector ".a" to come before selector ".b .a"
        "no-duplicate-selectors": null, // DISABLE: Unexpected duplicate selector ".a", first used at line XXX

        // DISABLED code validity rules, override MUST be removed ASAP
        "no-invalid-position-at-import-rule": null,
    },
};
