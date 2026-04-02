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

import { expect, test } from "../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../pages/KnowbaseItemPage";
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from "../../utils/WorkerEntities";

test('Can set visibility dates and see the indicator', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create an article without start/end dates
    const id = await api.createItem('KnowbaseItem', {
        name       : 'KB article for set dates test',
        answer     : 'Test content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(id);

    // Indicator should not be visible before any dates are set
    await expect(kb.getVisibilityDatesIndicator()).toBeHidden();

    // Open panel and set dates
    await kb.doEnableSchedulePanel();
    await kb.getScheduledStartDateInput().fill('2026-05-01 00:00:00');
    await kb.getScheduledStartDateInput().press('Tab');
    await kb.getScheduledEndDateInput().fill('2026-12-31 23:59:00');
    await kb.getScheduledEndDateInput().press('Tab');
    await kb.doApplyVisibilityDates();

    // Indicator should now be visible
    await expect(kb.getVisibilityDatesIndicator()).toBeVisible();
    await expect(kb.getVisibilityDatesIndicator()).toContainText('Scheduled');

    // Reload and confirm dates are persisted
    await kb.goto(id);
    await expect(kb.getVisibilityDatesIndicator()).toBeVisible();
    await kb.getVisibilityDatesIndicator().click();
    await expect(kb.getScheduledStartDateInput()).toHaveValue('2026-05-01 00:00:00');
    await expect(kb.getScheduledEndDateInput()).toHaveValue('2026-12-31 23:59:00');
});

test('Can clear visibility dates and the indicator disappears', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create an article with start/end dates
    const id = await api.createItem('KnowbaseItem', {
        name       : 'KB article for clear dates test',
        answer     : 'Test content',
        begin_date : '2026-05-01 00:00:00',
        end_date   : '2026-12-31 23:59:00',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(id);

    // Indicator should be visible since dates are set, click it to edit values
    await kb.getVisibilityDatesIndicator().click();

    // Clear both dates and apply
    await kb.getScheduledStartDateInput().clear();
    await kb.getScheduledStartDateInput().press('Tab');
    await kb.getScheduledEndDateInput().clear();
    await kb.getScheduledEndDateInput().press('Tab');
    await kb.doApplyVisibilityDates();

    // Indicator should disappear
    await expect(kb.getVisibilityDatesIndicator()).toBeHidden();

    // Reload and confirm cleared state is persisted
    await kb.goto(id);
    await expect(kb.getVisibilityDatesIndicator()).toBeHidden();
    await kb.doEnableSchedulePanel();
    await expect(kb.getScheduledStartDateInput()).toHaveValue('');
    await expect(kb.getScheduledEndDateInput()).toHaveValue('');
});
