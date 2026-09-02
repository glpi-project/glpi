/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

import { Locator, Page } from "@playwright/test";
import { GlpiPage } from "./GlpiPage";

/**
 * Reauth ("sudo" mode) prompt shown when a user tries to reach a page that
 * requires re-authentication without a valid reauth session.
 *
 * @see templates/pages/reauth/prompt.html.twig
 */
export class ReAuthPromptPage extends GlpiPage
{
    public readonly heading: Locator;
    public readonly password_field: Locator;
    public readonly verify_button: Locator;
    public readonly cancel_link: Locator;
    public readonly failure_alert: Locator;

    public constructor(page: Page)
    {
        super(page);
        this.heading = page.getByText('Re-authentication required');
        // The password input has no id/label association nor an ARIA role, so
        // getByRole()/getByLabel() cannot target it; fall back to its placeholder.
        this.password_field = page.getByPlaceholder('Password');
        this.verify_button = page.getByRole('button', { name: 'Verify' });
        this.cancel_link = page.getByRole('link', { name: 'Cancel' });
        this.failure_alert = page.getByText('Authentication failure');
    }

    /**
     * Fill the password and submit the reauth prompt.
     */
    public async doVerify(password: string): Promise<void>
    {
        await this.password_field.fill(password);
        await this.verify_button.click();
    }

    /**
     * Cancel the reauth prompt, returning to the origin page.
     */
    public async doCancel(): Promise<void>
    {
        await this.cancel_link.click();
    }
}
