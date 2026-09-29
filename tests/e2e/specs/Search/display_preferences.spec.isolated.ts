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

import type { Locator, Page } from '@playwright/test';
import { expect, test } from '../../fixtures/glpi_fixture';
import { DisplayPreferencePage } from '../../pages/DisplayPreferencePage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId, getWorkerUserId } from '../../utils/WorkerEntities';

const GLOBAL_VIEW = 'Global View';
const HELPDESK_VIEW = 'Helpdesk View';

// Column that no view uses by default, thus adding it proves that the tested
// view is the one being changed.
const TESTED_OPTION = 'Pending reason';

function getColumnHeader(page: Page, name: string): Locator
{
    return page.getByRole('columnheader', { name: name });
}

/**
 * Check that the option was added to one view only.
 */
async function expectOptionInViewOnly(
    page: Page,
    present_in: string,
    absent_from: string,
): Promise<void> {
    const preferences = new DisplayPreferencePage(page);
    const frame = await preferences.open();

    await preferences.goToTab(frame, present_in);
    await expect(preferences.getOptionRow(frame, TESTED_OPTION)).toBeVisible();

    await preferences.goToTab(frame, absent_from);
    await expect(preferences.getOptionRow(frame, TESTED_OPTION)).toBeHidden();
}

test.describe('Display preferences', () => {
    let ticket_id: number;

    test.beforeEach(async ({ api, profile }) => {
        // The ticket list only renders its columns when it holds at least one
        // row. The worker user must be the requester, otherwise the ticket
        // would not be visible with the self-service profile.
        ticket_id = await api.createItem('Ticket', {
            'name': 'Display preferences test ticket',
            'content': 'Display preferences test ticket',
            'entities_id': getWorkerEntityId(),
            '_users_id_requester': getWorkerUserId(),
        });

        await profile.set(Profiles.SuperAdmin);
    });

    // The global and the helpdesk views are shared by every user of the
    // application, thus the added column must be removed whatever happened
    // during the test.
    test.afterEach(async ({ page, api, profile }) => {
        await profile.set(Profiles.SuperAdmin);
        await page.goto('/front/ticket.php');

        const preferences = new DisplayPreferencePage(page);
        const frame = await preferences.open();
        await preferences.removeOptionIfPresent(frame, GLOBAL_VIEW, TESTED_OPTION);
        await preferences.removeOptionIfPresent(frame, HELPDESK_VIEW, TESTED_OPTION);

        await api.purgeItem('Ticket', ticket_id);
    });

    test('can add a column to the global view', async ({ page, profile }) => {
        await page.goto('/front/ticket.php');
        const preferences = new DisplayPreferencePage(page);
        const frame = await preferences.open();

        await preferences.goToTab(frame, GLOBAL_VIEW);
        await preferences.addOption(frame, TESTED_OPTION);

        // The central ticket list must show the new column.
        await page.goto('/front/ticket.php');
        await expect(getColumnHeader(page, TESTED_OPTION)).toBeVisible();

        // The helpdesk ticket list must not show it.
        await profile.set(Profiles.SelfService);
        await page.goto('/front/ticket.php');
        await expect(getColumnHeader(page, TESTED_OPTION)).toBeHidden();

        await profile.set(Profiles.SuperAdmin);
        await page.goto('/front/ticket.php');
        await expectOptionInViewOnly(page, GLOBAL_VIEW, HELPDESK_VIEW);
    });

    test('can add a column to the helpdesk view', async ({ page, profile }) => {
        await page.goto('/front/ticket.php');
        const preferences = new DisplayPreferencePage(page);
        const frame = await preferences.open();

        await preferences.goToTab(frame, HELPDESK_VIEW);
        await preferences.addOption(frame, TESTED_OPTION);

        // The central ticket list must not show the new column.
        await page.goto('/front/ticket.php');
        await expect(getColumnHeader(page, TESTED_OPTION)).toBeHidden();

        // The helpdesk ticket list must show it.
        await profile.set(Profiles.SelfService);
        await page.goto('/front/ticket.php');
        await expect(getColumnHeader(page, TESTED_OPTION)).toBeVisible();

        await profile.set(Profiles.SuperAdmin);
        await page.goto('/front/ticket.php');
        await expectOptionInViewOnly(page, HELPDESK_VIEW, GLOBAL_VIEW);
    });
});
