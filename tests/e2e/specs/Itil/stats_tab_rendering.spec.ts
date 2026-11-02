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
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe('Problem statistics tab', () => {
    let glpi_page: GlpiPage;
    let problem_id: number;

    test.beforeEach(async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        glpi_page = new GlpiPage(page);

        problem_id = await api.createItem('Problem', {
            name: `Stats tab problem - ${randomUUID()}`,
            content: 'Problem used to check the statistics tab rendering',
            entities_id: getWorkerEntityId(),
        });
    });

    test('Statistics tab renders both dates and times tables', async ({ page }) => {
        await page.goto(`/front/problem.form.php?id=${problem_id}`);
        // A real click exercises the AJAX tab load, unlike a forcetab URL.
        await glpi_page.doGoToTab('Statistics');

        // Dates table (stats_dates.html.twig).
        await expect(page.getByRole('heading', { name: 'Dates', level: 2, exact: true })).toBeVisible();
        await expect(page.getByRole('rowheader', { name: 'Opening date', exact: true })).toBeVisible();
        await expect(page.getByRole('rowheader', { name: 'Time to resolve', exact: true })).toBeVisible();

        // Times table (stats_times.html.twig).
        await expect(page.getByRole('heading', { name: 'Times', level: 2, exact: true })).toBeVisible();
        await expect(page.getByRole('rowheader', { name: 'Pending', exact: true })).toBeVisible();
    });
});
