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

import { Page } from "@playwright/test";
import { GlpiPage } from "./GlpiPage";
import {expect} from "../fixtures/glpi_fixture";

export class PlanningPage extends GlpiPage
{
    public constructor(page: Page)
    {
        super(page);
    }

    public async goto(): Promise<void>
    {
        await this.page.goto(`/front/planning.php`);
    }

    public async gotoView(view: string): Promise<void>
    {
        const view_button = this.page.getByRole('button', { name: view, exact: true });
        if (await view_button.getAttribute('aria-pressed') !== 'true') {
            await view_button.click();
        }
    }

    public async fillNewEventForm(input: {name: string, description: string, start_time: string, period: string}): Promise<void>
    {
        const dialog = this.page.getByRole('dialog', { name: 'Add an event' });
        await expect(dialog).toBeVisible();
        await dialog.getByRole('textbox', { name: 'Title' }).fill(input.name);
        await this.getRichTextByLabel('Description', dialog).fill(input.description);
        // eslint-disable-next-line playwright/no-raw-locators
        const start_date_input = dialog.locator('label', { hasText: 'Start date' }).locator('+ div input:not(.flatpickr-input)');
        const date = (await start_date_input.inputValue()).split(' ')[0];
        await start_date_input.fill(`${date} ${input.start_time}`);
        await this.page.getByRole('button', { name: 'Save' }).click();
        // eslint-disable-next-line playwright/no-raw-locators
        const period_select = dialog.locator('label', { hasText: 'Period' }).locator('+ div .select2').getByRole('combobox');
        await this.doSetDropdownValue(period_select, input.period);
        await dialog.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(dialog).toBeHidden();
    }
}
