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

export class LoginPage extends GlpiPage
{
    public readonly login_field: Locator;
    public readonly password_field: Locator;
    public readonly remember_me_checkbox: Locator;
    public readonly submit_button: Locator;


    public constructor(page: Page)
    {
        super(page);

        // Define the locators.
        this.login_field          = this.getTextbox("Login");
        this.password_field       = page.getByLabel('Password');
        this.remember_me_checkbox = this.getCheckbox("Remember me");
        this.submit_button        = this.getButton("Sign in");
    }

    public async goto(): Promise<void>
    {
        await this.page.goto("/");
    }

    public async login(login: string, password: string): Promise<void>
    {
        await this.login_field.fill(login);
        await this.password_field.fill(password);
        await this.remember_me_checkbox.check();
        await this.submit_button.click();
    }
}
