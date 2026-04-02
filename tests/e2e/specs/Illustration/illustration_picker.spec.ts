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

import { expect, test } from '../../fixtures/glpi_fixture';
import { IllustrationPickerPage } from '../../pages/IllustrationPickerPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe('Illustration picker', () => {
    let form_id: number;

    test.beforeEach(async ({ api }) => {
        form_id = await api.createItem('Glpi\\Form\\Form', {
            name: 'Test form for illustration picker',
            entities_id: getWorkerEntityId(),
        });
    });

    test('Can pick an image', async ({ profile, page }) => {
        await profile.set(Profiles.SuperAdmin);
        const picker_page = new IllustrationPickerPage(page);
        await picker_page.gotoFormServiceCatalogTab(form_id);

        // The default icon should be selected.
        await expect(picker_page.getIllustration('Request a service')).toBeVisible();

        // Open icon picker and select another icon
        await picker_page.doOpenIllustrationPicker();
        await picker_page.doSelectIllustration('Cartridge');

        // The newly selected icon must be displayed
        await expect(picker_page.getIllustration('Cartridge')).toBeVisible();
        await expect(picker_page.getIllustration('Request a service')).not.toBeAttached();

        // Save and make sure the newly selected image is here
        await page.getByRole('button', { name: 'Save changes' }).click();
        await expect(picker_page.getIllustration('Cartridge')).toBeVisible();
        await expect(picker_page.getIllustration('Request a service')).not.toBeAttached();
    });

    test('Can use pagination', async ({ profile, page }) => {
        await profile.set(Profiles.SuperAdmin);
        const picker_page = new IllustrationPickerPage(page);
        await picker_page.gotoFormServiceCatalogTab(form_id);

        const icons_from_first_page = [
            'Cartridge',
            'Desktop 1',
            'Network equipment',
        ];
        const icons_from_second_page = [
            'Browse help articles',
            'Area chart',
            'Search chart',
        ];

        // We are on the first page by default
        await picker_page.doOpenIllustrationPicker();
        for (const name of icons_from_first_page) {
            await expect(picker_page.picker_modal.getByRole('img', { name })).toBeVisible();
        }
        for (const name of icons_from_second_page) {
            await expect(picker_page.picker_modal.getByRole('img', { name })).not.toBeAttached();
        }

        // Go to second page
        await picker_page.doGoToPage(2);
        for (const name of icons_from_first_page) {
            await expect(picker_page.picker_modal.getByRole('img', { name })).not.toBeAttached();
        }
        for (const name of icons_from_second_page) {
            await expect(picker_page.picker_modal.getByRole('img', { name })).toBeVisible();
        }
        await expect(picker_page.picker_modal).toBeVisible();
    });

    test('Can search for icons', async ({ profile, page }) => {
        await profile.set(Profiles.SuperAdmin);
        const picker_page = new IllustrationPickerPage(page);
        await picker_page.gotoFormServiceCatalogTab(form_id);

        await picker_page.doOpenIllustrationPicker();
        await picker_page.doSearchIllustrations("Business Intelligence and Reporting");

        const expected_icons = [
            'Business Intelligence and Reporting 1',
            'Business Intelligence and Reporting 2',
            'Business Intelligence and Reporting 3',
            'Business Intelligence and Reporting 4',
        ];

        // Only the matching icons must be found
        await expect(picker_page.getModalImages()).toHaveCount(expected_icons.length);
        for (const name of expected_icons) {
            await expect(picker_page.picker_modal.getByRole('img', { name })).toBeVisible();
        }
    });

    test('Can upload and use a custom icon', async ({ profile, page }) => {
        await profile.set(Profiles.SuperAdmin);
        const picker_page = new IllustrationPickerPage(page);
        await picker_page.gotoFormServiceCatalogTab(form_id);

        // The default icon should be selected
        await expect(picker_page.getIllustration('Request a service')).toBeVisible();

        // Open icon picker and upload a custom icon
        await picker_page.doOpenIllustrationPicker();
        await picker_page.doUploadCustomIllustration("uploads/bar.png");

        // Make sure the custom image is displayed and is valid
        const custom_preview = picker_page.getCustomPreview();
        const custom_img = custom_preview.getByRole('img');
        await expect(custom_img).toBeVisible();
        const natural_width = await custom_img.evaluate(
            (img: HTMLImageElement) => img.naturalWidth
        );
        expect(natural_width).toBeGreaterThan(0);

        // Save changes
        await page.getByRole('button', { name: 'Save changes' }).click();
        await expect(custom_preview.getByRole('img')).toBeVisible();
    });

    test('Can pick an image searchable by tag', async ({ profile, page }) => {
        await profile.set(Profiles.SuperAdmin);
        const picker_page = new IllustrationPickerPage(page);
        await picker_page.gotoFormServiceCatalogTab(form_id);

        // The default icon should be selected
        await expect(picker_page.getIllustration('Request a service')).toBeVisible();

        // Open icon picker and search by tag
        await picker_page.doOpenIllustrationPicker();
        await picker_page.doSearchIllustrations("planet");

        // Only 1 icon must be found
        await expect(picker_page.getModalImages()).toHaveCount(1);

        // The icon must be the one we are looking for
        await expect(picker_page.picker_modal.getByRole('img', { name: 'World' })).toBeVisible();
    });
});
