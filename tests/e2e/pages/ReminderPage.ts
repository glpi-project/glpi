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

export class ReminderPage extends GlpiPage
{
    public readonly form: Locator;
    public readonly title_input: Locator;
    public readonly add_to_schedule_button: Locator;
    public readonly add_button: Locator;
    public readonly all_day_radio: Locator;
    public readonly time_slot_radio: Locator;
    public readonly all_day_label: Locator;
    public readonly start_time_input: Locator;
    public readonly end_time_input: Locator;
    public readonly plan_begin_input: Locator;
    public readonly plan_end_input: Locator;

    public constructor(page: Page)
    {
        super(page);

        this.form                   = page.getByRole('tabpanel');
        this.title_input            = this.form.getByLabel('Title', { exact: true });
        this.add_to_schedule_button = this.form.getByRole('button', { name: 'Add to schedule' });
        this.add_button             = this.form.getByRole('button', { name: 'Add', exact: true });
        this.all_day_radio          = this.form.getByRole('radio', { name: 'All day' });
        this.time_slot_radio        = this.form.getByRole('radio', { name: 'Time slot' });
        // Radios are rendered as a segmented control, only their labels can be clicked
        this.all_day_label          = this.form.getByText('All day', { exact: true });
        this.start_time_input       = this.form.getByRole('textbox', { name: 'Start time' });
        this.end_time_input         = this.form.getByRole('textbox', { name: 'End time' });
        // Submitted values are stored in hidden inputs
        this.plan_begin_input       = this.form.getByTestId('planning-event-begin');
        this.plan_end_input         = this.form.getByTestId('planning-event-end');
    }

    public async goto(): Promise<void>
    {
        await this.page.goto('/front/reminder.form.php');
    }

    public async doAddToSchedule(): Promise<void>
    {
        await this.add_to_schedule_button.click();
    }

    public async doSwitchToAllDay(): Promise<void>
    {
        await this.all_day_label.click();
    }

    public async doSave(): Promise<void>
    {
        await this.add_button.click();
    }

    public getPlanningSummary(): Locator
    {
        return this.form.getByText(/^\s*From .* to .*/);
    }
}
