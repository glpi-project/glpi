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

import { test, expect } from '../../fixtures/glpi_fixture';
import { GlpiPage } from '../../pages/GlpiPage';
import { Profiles } from '../../utils/Profiles';

const dashboards: Array<[string, string, number]> = [
    ['Asset', '/front/dashboard_assets.php', 15],
    ['Assistance', '/front/dashboard_helpdesk.php', 16],
    ['Central', '/front/central.php', 26],
    ['Tickets mini', '/front/ticket.php', 7],
];

for (const [name, url, number] of dashboards) {
    test(`Test dashboard "${name}" loads`, async ({page, profile}) => {
        // Load the dashboard
        await profile.set(Profiles.SuperAdmin);
        await page.goto(url);

        // Charts should all be visible and have the correct heigh
        const glpi_page = new GlpiPage(page);
        const widgets = glpi_page.dashboards_widgets;
        await expect(widgets).toHaveCount(number, {
            // Dashboards can be long to fully load
            timeout: 20000,
        });

        for (const widget of await widgets.all()) {
            await expect(widget).toBeVisible();
            const box = await widget.boundingBox();
            expect(box?.height).toBeGreaterThan(30);
        }
    });
}
