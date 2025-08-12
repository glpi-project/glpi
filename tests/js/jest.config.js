/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

module.exports = {
    projects: [
        {
            displayName: 'units',
            testMatch: ['<rootDir>/*.test.js', '<rootDir>/modules/**/*.test.js'],
            setupFilesAfterEnv: ["<rootDir>/jest-setup.mjs"],
            setupFiles: ['<rootDir>/bootstrap.mjs'],
            moduleDirectories: ['js/modules', 'tests/js/modules', 'node_modules'],
            moduleFileExtensions: ['js'],
            moduleNameMapper: {
                '^/js/(.*)$': '<rootDir>/../../js/$1',
                '^/build/(.*)$': '<rootDir>/../../public/build/$1',
                '^/lib/(.*)$': '<rootDir>/../../public/lib/$1',
            },
            transform: {},
            transformIgnorePatterns: [
                "/node_modules/(?!@tabler).+\\.js$"
            ],
            testEnvironment: 'jsdom',
            slowTestThreshold: 10,
        },
        {
            displayName: 'vue',
            testMatch: ['<rootDir>/vue/**/*.test.js'],
            setupFilesAfterEnv: ["<rootDir>/jest-setup.mjs"],
            setupFiles: ['<rootDir>/bootstrap.mjs'],
            moduleNameMapper: {
                '^/js/(.*)$': '<rootDir>/../../js/$1',
                '^/build/(.*)$': '<rootDir>/../../public/build/$1',
                '^/lib/(.*)$': '<rootDir>/../../public/lib/$1',
            },
            transform: {
                '^.+\\.vue$': '@vue/vue3-jest',
                '^.+\\.js$': 'babel-jest'
            },
            transformIgnorePatterns: [
                // Vue, @vue and @tabler are not transpiled by babel
                "<rootDir>/../../node_modules/(?!(@vue|vue|@tabler)/)"
            ],
            testEnvironment: 'jsdom',
            testEnvironmentOptions: {
                customExportConditions: ['node', 'node-addons']
            }
        }
    ]
};
