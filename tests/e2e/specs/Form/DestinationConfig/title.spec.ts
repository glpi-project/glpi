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

import { test, expect } from '../../../fixtures/glpi_fixture';
import { Profiles } from "../../../utils/Profiles";
import { FormPage } from "../../../pages/FormPage";

test.describe('Title configuration', () => {
    let form_page: FormPage;

    test.beforeEach(async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);
        const info = await formImporter.importForm('destination_config_fields/content-title-config.json');
        await form_page.gotoDestinationTab(info.getId());
    });

    test('Can configure the title field', async () => {
        const title_region = form_page.getRegion('Title configuration');
        // eslint-disable-next-line playwright/no-raw-locators
        const title_body = title_region.frameLocator('.tox-edit-area__iframe').locator('body');
        await title_body.click();
        await title_body.clear();
        await title_body.pressSequentially('My specific form name');
        await form_page.doSaveDestination();

        const title_region_after = form_page.getRegion('Title configuration');
        // eslint-disable-next-line playwright/no-raw-locators
        const title_body_after = title_region_after.frameLocator('.tox-edit-area__iframe').locator('body');
        await expect(title_body_after).toContainText('My specific form name');
    });

    test('Can create ticket using default configuration', async ({ page }) => {
        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();

        await page.getByRole('textbox', { name: 'What is your name ?' }).fill('John doe');
        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My form name' }).click();

        await expect(page.getByRole('heading')).toContainText('My form name');
    });
});
