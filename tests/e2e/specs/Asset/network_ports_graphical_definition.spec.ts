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
import { NetworkEquipmentModelPage } from '../../pages/NetworkEquipmentModelPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

const STENCIL_TAB = 'NetworkEquipmentModelStencil$1';

test.describe('Network ports graphical definition', () => {
    test('should manage network ports graphical definition', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);

        const model_id = await api.createItem('NetworkEquipmentModel', {
            name: 'Test Network Equipment Model',
        });

        const model_page = new NetworkEquipmentModelPage(page);

        // Upload an image for the front view of the network equipment
        await model_page.goto(model_id);
        await model_page.doUploadFrontImage('uploads/bar.png');
        await model_page.doSaveForm();

        // Navigate to the graphical slot definition tab and set up 10 ports
        await model_page.goto(model_id, STENCIL_TAB);
        await model_page.doSetPortCount(10);

        // Verify that all 10 port buttons are created with default styling
        for (let i = 1; i <= 10; i++) {
            const port_button = model_page.getPortButton(String(i));
            await expect(port_button).toBeVisible();
            await expect(port_button).not.toHaveClass(/btn-success/);
            await expect(port_button).not.toHaveClass(/btn-warning/);
        }

        // Configure port positions on the image
        await model_page.doConfigurePort('1', 50, 50, 50, 50);
        await model_page.doConfigurePort('2', 150, 150, 50, 50);
        await model_page.doConfigurePort('3', 250, 250, 50, 50);

        // Reload page to verify data persistence
        await page.reload();

        // Test reset functionality on port 2
        await model_page.doResetPort('2');
        const port2_button = model_page.getPortButton('2');
        await expect(port2_button).not.toHaveClass(/btn-success/);
        await expect(port2_button).not.toHaveClass(/btn-warning/);
        await expect(port2_button).toHaveClass(/btn-outline-secondary/);

        // Test adding and removing additional zones
        await model_page.doAddZone();
        await expect(model_page.getPortButton('11')).toBeVisible();

        await model_page.doRemoveZone();
        await expect(model_page.getPortButton('11')).toBeHidden();

        // Test clear all data functionality with confirmation
        await expect(model_page.getButton('Are you sure?')).toBeHidden();
        await model_page.getButton('Clear data').click();
        await expect(model_page.getButton('Are you sure?')).toBeVisible();
        await model_page.getButton('Are you sure?').click();

        // Verify that the interface is reset to initial state
        await expect(model_page.ports_count_input).toBeVisible();
    });

    test('should view defined ports', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);

        const model_id = await api.createItem('NetworkEquipmentModel', {
            name: 'Test Network Equipment Model',
        });

        const model_page = new NetworkEquipmentModelPage(page);

        // Upload an image and save
        await model_page.goto(model_id);
        await model_page.doUploadFrontImage('uploads/bar.png');
        await model_page.doSaveForm();

        // Navigate to the graphical slot definition tab and set up 5 ports
        await model_page.goto(model_id, STENCIL_TAB);
        await model_page.doSetPortCount(5);

        // Configure ports with labels
        for (let i = 1; i <= 5; i++) {
            await model_page.doConfigurePortWithLabel(
                String(i),
                `Port ${i}`,
                50 * i,
                50 * i,
                50,
                50,
            );
        }

        // Create a network equipment using this model
        const equipment_id = await api.createItem('NetworkEquipment', {
            name: 'Test Network Equipment',
            networkequipmentmodels_id: model_id,
            entities_id: getWorkerEntityId(),
        });

        // Add network ports with different statuses (ifstatus 1–5 maps to
        // Connected, Not connected, Testing, Unknown, Dormant)
        for (let i = 1; i <= 5; i++) {
            await api.createItem('NetworkPort', {
                name: `Port ${i}`,
                logical_number: i,
                itemtype: 'NetworkEquipment',
                items_id: equipment_id,
                ifstatus: i,
                entities_id: getWorkerEntityId(),
            });
        }

        // Navigate to the network ports tab of the equipment
        await page.goto(`/front/networkequipment.form.php?id=${equipment_id}&forcetab=NetworkPort$1`);

        const stencil_view = page.getByTestId('stencil-view');

        // Verify that the 5 ports are displayed with correct status indicators
        const port1_link = stencil_view.getByRole('link', { name: 'Port 1' });
        await expect(port1_link.getByTitle('Connected')).toHaveClass(/status-green/);

        const port2_link = stencil_view.getByRole('link', { name: 'Port 2' });
        await expect(port2_link.getByTitle('Not connected')).toHaveClass(/status-red/);

        const port3_link = stencil_view.getByRole('link', { name: 'Port 3' });
        await expect(port3_link.getByTitle('Testing')).toHaveClass(/status-orange/);

        const port4_link = stencil_view.getByRole('link', { name: 'Port 4' });
        await expect(port4_link.getByTitle('Unknown')).toHaveClass(/status-muted/);

        const port5_link = stencil_view.getByRole('link', { name: 'Port 5' });
        await expect(port5_link.getByTitle('Dormant')).toHaveClass(/status-muted/);
    });
});
