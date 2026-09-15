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
import { randomUUID } from 'crypto';
import { expect, test } from '../../fixtures/glpi_fixture';
import { GlpiPage } from '../../pages/GlpiPage';
import { Api } from '../../utils/Api';
import { Profiles } from '../../utils/Profiles';
import {
    getWorkerEntityId,
    getWorkerIndex,
} from '../../utils/WorkerEntities';

test.describe('Reservations', () => {
    type Fixtures = {
        computer_id: number,
        computer_reservationitem_id: number,
        computer_name: string,
        monitor_name: string,
    };

    const getWorkerFriendlyName = (): string => {
        const index = String(getWorkerIndex()).padStart(2, '0');
        return `E2E worker account ${index}`;
    };

    const setupReservableItems = async (api: Api): Promise<Fixtures> => {
        const uuid = randomUUID();

        const computer_name = `Reservable computer ${uuid}`;
        const computer_id = await api.createItem('Computer', {
            name: computer_name,
            entities_id: getWorkerEntityId(),
        });
        const computer_reservationitem_id = await api.createItem(
            'ReservationItem',
            { itemtype: 'Computer', items_id: computer_id }
        );

        const monitor_name = `Reservable monitor ${uuid}`;
        const monitor_id = await api.createItem('Monitor', {
            name: monitor_name,
            entities_id: getWorkerEntityId(),
        });
        await api.createItem('ReservationItem', {
            itemtype: 'Monitor',
            items_id: monitor_id,
        });

        return {
            computer_id,
            computer_reservationitem_id,
            computer_name,
            monitor_name,
        };
    };

    test('Create a reservation and view', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const fixtures = await setupReservableItems(api);
        await page.goto(
            `/front/reservation.php?reservationitems_id=${fixtures.computer_reservationitem_id}`
        );

        // eslint-disable-next-line playwright/no-raw-locators
        await page.locator('.fc-week .fc-day').first().click();

        const add_dialog = page.getByRole('dialog', {
            name: 'Add reservation',
        });
        await expect(add_dialog).toContainText(fixtures.computer_name);
        await glpi_page.doSearchAndClickDropdownValue(
            glpi_page.getDropdownByLabel('By', add_dialog),
            getWorkerFriendlyName()
        );
        await add_dialog.getByRole('button', { name: 'Add' }).click();

        // eslint-disable-next-line playwright/no-raw-locators
        await page.locator('.fc-day-grid-event').first().click();

        const edit_dialog = page.getByRole('dialog', {
            name: 'Edit reservation',
        });
        await expect(edit_dialog).toContainText(fixtures.computer_name);
        await edit_dialog.getByRole('button', { name: 'Close' }).click();
    });

    test('Find available reservation items', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);

        const fixtures = await setupReservableItems(api);
        await page.goto('/front/reservationitem.php');

        const tabpanel = page.getByRole('tabpanel');
        await tabpanel.getByRole('button', {
            name: /Find a free item in a specific period/,
        }).click();

        const itemtype_select = tabpanel.getByLabel('Item type');
        const cells = tabpanel.getByRole('cell');

        // The expected item must be asserted first: the search submits the
        // form, so a "no such item" assertion would otherwise be satisfied by
        // the results of the previous search.
        await itemtype_select.selectOption('Monitor');
        await tabpanel.getByRole('button', { name: 'Search' }).click();
        await expect(
            cells.filter({ hasText: `Monitors - ${fixtures.monitor_name}` })
        ).toHaveCount(1);
        await expect(cells.filter({ hasText: 'Computers - ' })).toHaveCount(0);

        await itemtype_select.selectOption('Computer');
        await tabpanel.getByRole('button', { name: 'Search' }).click();
        await expect(
            cells.filter({ hasText: `Computers - ${fixtures.computer_name}` })
        ).toHaveCount(1);
        await expect(cells.filter({ hasText: 'Monitors - ' })).toHaveCount(0);
    });

    test('Month and Year context preserved', async ({ page, profile, api }) => {
        /**
         * Ensure the month and year context is preserved when adding, updating or deleting a reservation.
         * For example, if you add a reservation in October 2024, after saving, the calendar should still display October 2025 when the page reloads or the user is redirected back.
         * We will test with a past date to ensure it's not the real current month.
         *
         * We will also test the user isn't redirected to a different page.
         * For example, if the user adds a reservation from the asset form, after saving, they should still be in the asset form, not redirected to the reservations page.
         */
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const fixtures = await setupReservableItems(api);

        /* eslint-disable playwright/no-raw-locators */
        const toolbar = page.locator('.fc-header-toolbar');
        const current_period = page.locator('.fc-header-toolbar .fc-center');
        const events = page.locator('.fc-day-grid-event');

        const doGotoOctober2024 = async (): Promise<void> => {
            await page.getByRole('button', { name: 'month' }).click();
            await expect(current_period).toContainText('November 2024');
            // Change month to ensure the back URL computes correctly
            await toolbar.getByLabel('prev').click();
            await expect(current_period).toContainText('October 2024');
        };

        const assertOctober2024 = async (): Promise<void> => {
            await expect(current_period).toContainText('October 2024');
            await expect(page).toHaveURL(
                /(defaultDate=2024-10-\d{2}(&|$))|(tab_params%5BdefaultDate%5D=2024-10-\d{2}(&|$))/
            );
        };

        const doAddReservation = async (): Promise<void> => {
            await page.locator('.fc-day-grid .fc-day:not(.fc-other-month)')
                .first()
                .click()
            ;
            const dialog = page.getByRole('dialog', {
                name: 'Add reservation',
            });
            await expect(dialog).toContainText(fixtures.computer_name);
            await glpi_page.doSearchAndClickDropdownValue(
                glpi_page.getDropdownByLabel('By', dialog),
                getWorkerFriendlyName()
            );
            await dialog.getByRole('button', { name: 'Add' }).click();
        };

        const doUpdateReservation = async (): Promise<void> => {
            await events.first().click();
            const dialog = page.getByRole('dialog', {
                name: 'Edit reservation',
            });
            await expect(dialog).toContainText(fixtures.computer_name);
            await dialog.getByRole('button', { name: 'Save' }).click();
        };

        const doDeleteReservation = async (): Promise<void> => {
            await events.first().click();
            const dialog = page.getByRole('dialog', {
                name: 'Edit reservation',
            });
            await expect(dialog).toContainText(fixtures.computer_name);

            page.once('dialog', (confirm) => confirm.accept());
            await dialog.getByRole('button', {
                name: 'Delete permanently',
            }).click();
        };
        /* eslint-enable playwright/no-raw-locators */

        const asset_form_url = `/front/computer.form.php?id=${fixtures.computer_id}&forcetab=Reservation$1&tab_params[month]=11&tab_params[year]=2024`;
        const reservations_url = `/front/reservation.php?reservationitems_id=${fixtures.computer_reservationitem_id}&month=11&year=2024`;

        // Test from Reservations tab in asset form
        await page.goto(asset_form_url);
        await doGotoOctober2024();
        await doAddReservation();
        await assertOctober2024();
        // Check we are still in the asset form
        await expect(page).toHaveURL(
            new RegExp(`computer\\.form\\.php\\?id=${fixtures.computer_id}`)
        );

        // Update the reservation and check we are still in October 2024
        await page.goto(asset_form_url);
        await doGotoOctober2024();
        await doUpdateReservation();
        await assertOctober2024();
        // Check we are still in the asset form
        await expect(page).toHaveURL(
            new RegExp(`computer\\.form\\.php\\?id=${fixtures.computer_id}`)
        );

        // Delete the reservation and check we are still in October 2024
        await page.goto(asset_form_url);
        await doGotoOctober2024();
        await doDeleteReservation();
        await assertOctober2024();
        // Check we are still in the asset form
        await expect(page).toHaveURL(
            new RegExp(`computer\\.form\\.php\\?id=${fixtures.computer_id}`)
        );

        // Test from reservations page
        await page.goto(reservations_url);
        await doGotoOctober2024();
        await doAddReservation();
        await assertOctober2024();
        // Check we are still in the reservations page
        await expect(page).toHaveURL(
            new RegExp(
                `reservationitems_id=${fixtures.computer_reservationitem_id}`
            )
        );

        await page.goto(reservations_url);
        await doGotoOctober2024();
        await doUpdateReservation();
        await assertOctober2024();
        // Check we are still in the reservations page
        await expect(page).toHaveURL(
            new RegExp(
                `reservationitems_id=${fixtures.computer_reservationitem_id}`
            )
        );

        // Delete the reservation and check we are still in October 2024
        await page.goto(reservations_url);
        await doGotoOctober2024();
        await doDeleteReservation();
        await assertOctober2024();
        // Check we are still in the reservations page
        await expect(page).toHaveURL(
            new RegExp(
                `reservationitems_id=${fixtures.computer_reservationitem_id}`
            )
        );
    });
});
