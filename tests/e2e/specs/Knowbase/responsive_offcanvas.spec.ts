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

test('Side panel displays as offcanvas on small screens', async ({
    page,
    profile,
    api
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create a KB item with a comment
    const id = await api.createItem('KnowbaseItem', {
        name: 'Responsive KB entry',
        entities_id: getWorkerEntityId(),
        answer: "Responsive test answer",
    });
    await api.createItem('KnowbaseItem_Comment', {
        knowbaseitems_id: id,
        comment: "Test comment for responsive view",
    });

    // Set viewport to mobile size (< 768px)
    await page.setViewportSize({ width: 600, height: 800 });

    // Go to article
    await kb.goto(id);
    await expect(page.getByText('Responsive test answer')).toBeVisible();

    // Open comments panel
    await page.getByTitle('More actions').click();
    await kb.getButton('Comments').click();

    // On small screens, an offcanvas should be disabled
    const offcanvas = page.getByTestId('offcanvas');
    const side_panel = page.getByTestId('side-panel');
    await expect(offcanvas).not.toBeEmpty();
    await expect(side_panel).toBeEmpty();

    // Comments should be visible within the offcanvas
    await expect(offcanvas.getByText('Test comment for responsive view').filter({
        'visible': true,
    })).toBeAttached();

    // Close the offcanvas
    const close_button = offcanvas.getByTestId('side-panel-close');
    await close_button.click();

    // Offcanvas should be hidden after close
    await expect(offcanvas).toBeHidden();
});

test('Side panel displays normally on large screens', async ({
    page,
    profile,
    api
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create a KB item with a comment
    const id = await api.createItem('KnowbaseItem', {
        name: 'Responsive KB entry',
        entities_id: getWorkerEntityId(),
        answer: "Responsive test answer",
    });
    await api.createItem('KnowbaseItem_Comment', {
        knowbaseitems_id: id,
        comment: "Test comment for responsive view",
    });

    // Go to article
    await kb.goto(id);
    await expect(page.getByText('Responsive test answer')).toBeVisible();

    // Open comments panel and verify it uses the side panel
    await page.getByTitle('More actions').click();
    await kb.getButton('Comments').click();

    // On default sized screens, a side panel should be disabled
    const offcanvas = page.getByTestId('offcanvas');
    const side_panel = page.getByTestId('side-panel');
    await expect(offcanvas).toBeEmpty();
    await expect(side_panel).not.toBeEmpty();

    // Comments should be visible within the side panel
    await expect(side_panel.getByText('Test comment for responsive view').filter({
        'visible': true,
    })).toBeAttached();

    // Close the side panel
    const close_button = side_panel.getByTestId('side-panel-close');
    await close_button.click();

    // Side panel should be reduced after close
    await expect(side_panel).toHaveCSS('width', '0px');
});
