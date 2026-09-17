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
import {expect} from "../fixtures/glpi_fixture";

export class PlanningPage extends GlpiPage
{
    public readonly new_event_dialog: Locator;
    public readonly all_day_radio: Locator;
    public readonly time_slot_radio: Locator;
    public readonly time_slot_label: Locator;
    public readonly start_time_input: Locator;
    public readonly end_time_input: Locator;
    public readonly plan_begin_input: Locator;
    public readonly plan_end_input: Locator;

    public constructor(page: Page)
    {
        super(page);

        this.new_event_dialog = page.getByRole('dialog', { name: 'Add an event' });
        this.all_day_radio    = this.new_event_dialog.getByRole('radio', { name: 'All day' });
        this.time_slot_radio  = this.new_event_dialog.getByRole('radio', { name: 'Time slot' });
        // Radios are rendered as a segmented control, only their labels can be clicked
        this.time_slot_label  = this.new_event_dialog.getByText('Time slot', { exact: true });
        this.start_time_input = this.new_event_dialog.getByRole('textbox', { name: 'Start time' });
        this.end_time_input   = this.new_event_dialog.getByRole('textbox', { name: 'End time' });
        // Submitted values are stored in hidden inputs
        this.plan_begin_input = this.new_event_dialog.getByTestId('planning-event-begin');
        this.plan_end_input   = this.new_event_dialog.getByTestId('planning-event-end');
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

    public async doFillNewEventForm(input: {name: string, description: string, start_time: string, end_time: string}): Promise<void>
    {
        const dialog = this.new_event_dialog;
        await expect(dialog).toBeVisible();
        await dialog.getByRole('textbox', { name: 'Title' }).fill(input.name);
        await this.getRichTextByLabel('Description', dialog).fill(input.description);

        // Clicking on a day creates an "All day" event, switch to a time slot to be able to set hours
        await expect(this.all_day_radio).toBeChecked();
        await expect(this.start_time_input).toBeHidden();
        await this.time_slot_label.click();
        await expect(this.time_slot_radio).toBeChecked();

        await this.start_time_input.fill(input.start_time);
        await this.start_time_input.press('Tab');
        await this.end_time_input.fill(input.end_time);
        await this.end_time_input.press('Tab');

        await expect(this.plan_begin_input).toHaveValue(new RegExp(` ${input.start_time}:00$`));
        await expect(this.plan_end_input).toHaveValue(new RegExp(` ${input.end_time}:00$`));

        await dialog.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(dialog).toBeHidden();
    }
}
