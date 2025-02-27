/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

import { test, expect } from '../../fixtures/authenticated';
import { FormPage } from '../../pages/FormPage';
import { GlpiApi } from '../../utils/GlpiApi';
import { SessionManager } from '../../utils/SessionManager';

test('tabs are refreshed on update', async ({ page, request }) => {
    // Load super admin profile
    const session = new SessionManager(request);
    await session.changeProfile("Super-Admin");

    // Create a test form
    const glpi_api = new GlpiApi();
    const form_id = await glpi_api.createItem('Glpi\\Form\\Form', {
        'name': '[Test] Ajax Controller: refresh tabs on update',
    });

    // Go to the history tab, which should have 3 entries
    const form_page = new FormPage(page);
    await form_page.goto(form_id);
    await form_page.goToTab("Historical 3");
    await expect(form_page.getHistoryRows()).toHaveCount(4); // 3 entries + header

    // Modify and save the form
    await form_page.goToTab("Form");
    await form_page.setActive();
    await form_page.saveFormEditor();

    // Go back to history tab, it must be updated with new entries
    await form_page.goToTab("Historical 3"); // Note that tab name isn't udpated so it still show 3 entries
    await expect(form_page.getHistoryRows()).toHaveCount(6); // 4 entries + header + first section visibility being initialized
});

