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

import { randomUUID } from "crypto";
import { test, expect } from '../../fixtures/glpi_fixture';
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { FormCategoryPage } from "../../pages/FormCategoryPage";
import { IllustrationPickerPage } from "../../pages/IllustrationPickerPage";

test('Can create a new form category', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const category_page = new FormCategoryPage(page);
    const illustration_picker = new IllustrationPickerPage(page);

    await category_page.gotoList();
    await page.getByRole('link', { name: 'Add' }).click();

    await category_page.name_input.fill('Test category');
    await category_page.description_input.fill('This is a test category');

    await illustration_picker.doOpenIllustrationPicker();
    await illustration_picker.doSelectIllustration('Cartridge');

    await category_page.getButton('Add').click();
    await page.getByRole('alert').getByRole('link', { name: 'Test category' }).click();

    await expect(category_page.name_input).toHaveValue('Test category');
    await expect(page.getByLabel('Description')).toHaveValue('<p>This is a test category</p>');
    await expect(category_page.description_input).toHaveText('This is a test category');
    await expect(page.getByRole('img', { name: 'Cartridge' })).toBeVisible();
});

test('Can open illustration picker, show forms attached to the category and go back to illustration picker', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const category_page = new FormCategoryPage(page);
    const illustration_picker = new IllustrationPickerPage(page);

    const category_id = await api.createItem('Glpi\\Form\\Category', {
        name: `Test category - ${randomUUID()}`,
    });
    await api.createItem('Glpi\\Form\\Form', {
        name: 'Test form',
        forms_categories_id: category_id,
        entities_id: getWorkerEntityId(),
    });

    await category_page.goto(category_id);

    await illustration_picker.doOpenIllustrationPicker();
    await illustration_picker.doSelectIllustration('Cartridge');
    await expect(page.getByRole('img', { name: 'Cartridge' })).toBeVisible();

    await category_page.gotoWithTab(category_id, 'Glpi\\Form\\Form$1');
    await expect(category_page.getLink('Test form')).toBeVisible();

    await category_page.gotoWithTab(category_id, 'Glpi\\Form\\Category$main');
    await illustration_picker.doOpenIllustrationPicker();
    await illustration_picker.doSelectIllustration('Cartridge');
    await expect(page.getByRole('img', { name: 'Cartridge' })).toBeVisible();
});
