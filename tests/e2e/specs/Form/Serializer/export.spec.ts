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
import { readFileSync } from 'fs';
import { expect, test } from '../../../fixtures/glpi_fixture';
import { FormPage } from '../../../pages/FormPage';
import { Profiles } from '../../../utils/Profiles';
import { getWorkerEntityId } from '../../../utils/WorkerEntities';

test.describe('Export forms', () => {
    test('Export single form', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const form_id = await api.createItem('Glpi\\Form\\Form', {
            'name': `My form ${randomUUID()}`,
            'entities_id': getWorkerEntityId(),
        });
        await form.goto(form_id);

        await page.getByRole('button', { name: "Actions" }).click();

        const download_promise = page.waitForEvent('download');
        await page.getByRole('button', { name: "Export form" }).click();

        const download = await download_promise;
        const json = JSON.parse(readFileSync(await download.path()).toString());
        expect(json.forms).toHaveLength(1);
    });

    test('Export multiple form', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        for (let i = 0; i < 3; i++) {
            await api.createItem('Glpi\\Form\\Form', {
                'name': `My test form ${randomUUID()}`,
                'entities_id': getWorkerEntityId(),
            });
        }

        await page.goto('/front/form/form.php');
        const checkboxes = page.getByRole('checkbox', { name: "Select item" });
        await checkboxes.nth(0).check();
        await checkboxes.nth(1).check();
        await checkboxes.nth(2).check();

        await page.getByRole('button', { name: "Actions" }).click();

        const download_promise = page.waitForEvent('download');
        await form.doSetDropdownValue(
            form.getDropdownByLabel('Action'),
            'Export form'
        );

        const download = await download_promise;
        const json = JSON.parse(readFileSync(await download.path()).toString());
        expect(json.forms).toHaveLength(3);
    });
});
