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
 * Dialog offering a re-authentication, shown when a request was rejected because it lacked one.
 *
 * The prompt is a full page, so it cannot be the answer of an AJAX request: the client gets a
 * marked 403 and offers this dialog instead.
 *
 * @see handleReauthRequiredResponse() in js/common.js
 */
export class ReAuthRequiredDialog extends GlpiPage
{
    public readonly dialog: Locator;
    public readonly continue_button: Locator;
    public readonly cancel_button: Locator;

    public constructor(page: Page)
    {
        super(page);
        this.dialog = page
            .getByRole('dialog', { name: 'Re-authentication required' })
            .filter({ visible: true });
        this.continue_button = this.dialog.getByRole('button', { name: 'Continue', exact: true });
        this.cancel_button = this.dialog.getByRole('button', { name: 'Cancel', exact: true });
    }

    /**
     * Accept the dialog, sending the browser to the re-authentication prompt.
     */
    public async doContinue(): Promise<void>
    {
        await this.continue_button.click();
    }
}
