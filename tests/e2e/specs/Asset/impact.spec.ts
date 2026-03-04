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
import { ImpactPage } from '../../pages/ImpactPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('Impact graph loads', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const computer_id = await api.createItem('Computer', {
        name: 'Impact computer',
        entities_id: getWorkerEntityId(),
    });

    const impact_page = new ImpactPage(page);
    await impact_page.gotoComputerImpact(computer_id);

    await expect(impact_page.graph_view).toBeVisible();
    await expect(impact_page.list_view).toBeHidden();
    expect(await impact_page.canvas_elements.count()).toBeGreaterThanOrEqual(1);

    await impact_page.view_as_list_button.click();
    await expect(impact_page.graph_view).toBeHidden();
    await expect(impact_page.list_view).toBeVisible();

    await impact_page.view_as_graph_button.click();
    await expect(impact_page.graph_view).toBeVisible();
    await expect(impact_page.list_view).toBeHidden();
});
