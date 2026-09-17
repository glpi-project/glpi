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

    public async fillNewEventForm(input: {name: string, description: string, start_time: string, end_time: string}): Promise<void>
    {
        const dialog = this.page.getByRole('dialog', { name: 'Add an event' });
        await expect(dialog).toBeVisible();
        await dialog.getByRole('textbox', { name: 'Title' }).fill(input.name);
        await this.getRichTextByLabel('Description', dialog).fill(input.description);

        // Clicking on a day creates an "All day" event, switch to a time slot to be able to set hours
        await expect(dialog.getByRole('radio', { name: 'All day' })).toBeChecked();
        await expect(dialog.getByRole('textbox', { name: 'Start time' })).toBeHidden();
        await dialog.getByText('Time slot').click();

        const start_time_input = dialog.getByRole('textbox', { name: 'Start time' });
        await start_time_input.fill(input.start_time);
        await start_time_input.press('Tab');
        const end_time_input = dialog.getByRole('textbox', { name: 'End time' });
        await end_time_input.fill(input.end_time);
        await end_time_input.press('Tab');

        // eslint-disable-next-line playwright/no-raw-locators
        await expect(dialog.locator('input[name="plan[begin]"]')).toHaveValue(new RegExp(` ${input.start_time}:00$`));
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(dialog.locator('input[name="plan[end]"]')).toHaveValue(new RegExp(` ${input.end_time}:00$`));

        await dialog.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(dialog).toBeHidden();
    }
}
