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

import { type Locator, type Page } from '@playwright/test';
import { GlpiPage } from './GlpiPage';

/**
 * POM for the login page.
 * See https://playwright.dev/docs/pom.
 */
export class LoginPage extends GlpiPage
{
    private readonly loginField: Locator;
    private readonly passwordField: Locator;
    private readonly rememberMeCheckbox: Locator;
    private readonly submitButton: Locator;

    // When called from the fixtures, the base_url from the global playwright
    // config is not available and must be specified manually when calling the
    // constructor of this page.
    // TODO: investigate and fix if needed.
    private readonly base_url: string;

    public constructor(page: Page, base_url: string = "") {
        super(page);
        this.base_url = base_url;

        // Define the locators.
        this.loginField         = page.getByRole('textbox', {'name': "Login"});
        this.passwordField      = page.getByLabel('Password');
        this.rememberMeCheckbox = page.getByRole('checkbox', {name: "Remember me"});
        this.submitButton       = page.getByRole('button', {name: "Sign in"});
    }

    public async goto() {
        await this.page.goto(this.base_url);
    }

    public async login(login: string, password: string) {
        await this.loginField.fill(login);
        await this.passwordField.fill(password);
        await this.rememberMeCheckbox.check();
        await this.submitButton.click();
    }
}
