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

test('Enabling public sharing appears in the history', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: `KB sharing history enable - ${crypto.randomUUID()}`,
        entities_id: getWorkerEntityId(),
        answer: 'Content for history-sharing enable',
    });

    await kb.goto(id);
    await kb.openSharePopover();
    await kb.publishSwitch().check();
    await expect(kb.shareLink()).toBeVisible();

    await kb.goto(id);
    await kb.doOpenHistoryPanel();
    await expect(kb.getHistoryEventByText('Sharing enabled')).toBeVisible();
});

test('Disabling public sharing appears in the history', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: `KB sharing history disable - ${crypto.randomUUID()}`,
        entities_id: getWorkerEntityId(),
        answer: 'Content for history-sharing disable',
    });

    await kb.goto(id);
    await kb.openSharePopover();
    await kb.publishSwitch().check();
    await expect(kb.shareLink()).toBeVisible();

    await kb.publishSwitch().uncheck();
    await expect(kb.shareLink()).not.toBeAttached();

    await kb.goto(id);
    await kb.doOpenHistoryPanel();
    await expect(kb.getHistoryEventByText('Sharing enabled')).toBeVisible();
    await expect(kb.getHistoryEventByText('Sharing disabled')).toBeVisible();
});
