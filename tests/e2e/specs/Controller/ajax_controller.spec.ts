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

import { test, expect } from '../../fixtures/glpi_fixture';
import { FormPage } from '../../pages/FormPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('Tabs are refreshed on update', async ({page, profile, api}) => {
    await profile.set(Profiles.SuperAdmin);

    // Create an item that use the ajax controller and go to its page
    const id = await api.createItem("Glpi\\Form\\Form", {
        name: "My form",
        entities_id: getWorkerEntityId(),
        is_active: false,
    });
    const form_page = new FormPage(page);
    await form_page.goto(id);

    // Load the history tab and count the history
    await form_page.doGoToTab("Historical");
    await expect(form_page.history_rows).toHaveCount(5);

    // Go back to the form and trigger an update, then go back to the history
    await form_page.doGoToTab("Form");
    await form_page.editor_active_checkbox.check();
    await form_page.doSaveFormEditor();
    await form_page.doGoToTab("Historical");

    // The history should be updated
    await expect(form_page.history_rows).toHaveCount(7);
});
