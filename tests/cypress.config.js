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

const { defineConfig } = require("cypress");
const execSync = require('child_process');

module.exports = defineConfig({
    viewportWidth: 1920,
    viewportHeight: 1080,
    experimentalStudio: true,
    e2e: {
        baseUrl: "http://localhost:80",
        experimentalMemoryManagement: true,
        retries: {
            runMode: 3,
        },
        setupNodeEvents(on) {
            // implement node event listeners here
            // Remove --start-maximized flag from Chrome
            // eslint-disable-next-line no-unused-vars
            on("before:browser:launch", (browser = {}, launchOptions) => {
                const maximized_index = launchOptions.args.indexOf("--start-maximized");
                if (maximized_index !== -1) {
                    launchOptions.args.splice(maximized_index, 1);
                }
                return launchOptions;
            });
            on('before:run', () => {
                // Make sure cached content like twig template is cleared before running the tests.
                execSync.execSync("../bin/console cache:clear --env='testing'");
            });
            on('task', {
                log(message) {
                    // eslint-disable-next-line no-console
                    console.log(message);
                    return null;
                },
                table(message) {
                    // eslint-disable-next-line no-console
                    console.table(message);
                    return null;
                },
                generateOTP: require('cypress-otp')
            });
        },
    },
});
