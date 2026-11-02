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
import { expect } from "../fixtures/glpi_fixture";

export class ReservationPage extends GlpiPage
{
    public constructor(page: Page)
    {
        super(page);
    }

    public async goto(
        reservationitems_id: number,
        default_date: string,
    ): Promise<void> {
        await this.page.goto(
            `/front/reservation.php?reservationitems_id=${reservationitems_id}&defaultDate=${default_date}`
        );
    }

    public async gotoView(view: string): Promise<void>
    {
        const view_button = this.getButton(view);
        if (await view_button.getAttribute('aria-pressed') !== 'true') {
            await view_button.click();
        }
    }

    /**
     * Reservation events are rendered by FullCalendar, we have no control over
     * their markup.
     */
    public getEvent(title: string): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators
        return this.page.locator('.fc-event').filter({ hasText: title });
    }

    /**
     * Day cells carry no accessible name of their own, the day number heading
     * FullCalendar renders inside them does.
     */
    public getDayCell(day_label: string): Locator
    {
        return this.page.getByRole('gridcell').filter({
            has: this.page.getByRole('heading', { name: day_label, exact: true }),
        });
    }

    /**
     * Drag an event (or one of its resize handles) onto another day cell.
     *
     * FullCalendar's drag&drop needs several intermediate mouse moves to pick
     * the drop target up, so `dragTo()` cannot be used here.
     */
    public async doDragToDay(source: Locator, day: Locator): Promise<void>
    {
        const from = await source.boundingBox();
        const to = await day.boundingBox();
        if (from === null || to === null) {
            throw new Error("Can't compute the drag coordinates");
        }

        const mouse = this.page.mouse;
        await mouse.move(from.x + from.width / 2, from.y + from.height / 2);
        await mouse.down();
        for (let step = 1; step <= 10; step++) {
            await mouse.move(
                from.x + from.width / 2 + (to.x + to.width / 2 - from.x - from.width / 2) * step / 10,
                from.y + from.height / 2 + (to.y + to.height / 2 - from.y - from.height / 2) * step / 10,
            );
        }
        await mouse.up();
    }

    /**
     * Drag an event's end handle down by the given height, to make it last
     * longer. Only available in the time grid views, FullCalendar does not
     * allow timed events to be resized from the month view.
     */
    public async doResizeEventBy(event: Locator, pixels: number): Promise<void>
    {
        // Resize handles are only rendered on hover.
        await event.hover();
        // The resize handle is rendered by FullCalendar and carries no role.
        // eslint-disable-next-line playwright/no-raw-locators
        const resizer = event.locator('.fc-event-resizer-end');
        const box = await resizer.boundingBox();
        if (box === null) {
            throw new Error("Can't compute the resize coordinates");
        }

        const x = box.x + box.width / 2;
        const y = box.y + box.height / 2;
        const mouse = this.page.mouse;
        await mouse.move(x, y);
        await mouse.down();
        for (let step = 1; step <= 10; step++) {
            await mouse.move(x, y + pixels * step / 10);
        }
        await mouse.up();
    }

    public async assertCalendarIsLoaded(): Promise<void>
    {
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(this.page.locator('div.fc')).toBeVisible();
    }
}
