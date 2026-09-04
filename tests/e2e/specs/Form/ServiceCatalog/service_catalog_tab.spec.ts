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
import { Page } from '@playwright/test';
import { randomUUID } from 'crypto';
import { expect, test } from '../../../fixtures/glpi_fixture';
import { GlpiPage } from '../../../pages/GlpiPage';
import { Api } from '../../../utils/Api';
import { Profiles } from '../../../utils/Profiles';
import { getWorkerEntityId } from '../../../utils/WorkerEntities';

test.describe('Service catalog tab', () => {
    // `Glpi\Form\Category` has no entity, so it is visible to every worker:
    // purge it once the test is done to keep the shared list small.
    const created_category_ids: number[] = [];

    test.afterEach(async ({ api }) => {
        while (created_category_ids.length > 0) {
            await api.purgeItem(
                'Glpi\\Form\\Category',
                created_category_ids.pop() as number
            );
        }
    });

    const setupCategory = async (api: Api): Promise<string> => {
        const uid = randomUUID();
        const category_name = `Category ${uid}`;

        created_category_ids.push(
            await api.createItem('Glpi\\Form\\Category', {
                'name': category_name,
                'description': "my description",
            })
        );

        return category_name;
    };

    const doSaveChanges = async (
        page: Page,
        glpi_page: GlpiPage
    ): Promise<void> => {
        // This button embeds an icon, its accessible name can not be matched
        // exactly.
        await page.getByRole('button', { name: "Save changes" }).click();
        await expect(glpi_page.getAlert('Item successfully updated'))
            .toBeVisible()
        ;
    };

    test('can configure service catalog for form', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const category_name = await setupCategory(api);
        // GLPI add "»" prefix to common tree dropdown values
        const category_dropdown_value = `»${category_name}`;

        const form_id = await api.createItem('Glpi\\Form\\Form', {
            'name': `Test form for service_catalog_tab.cy.js ${randomUUID()}`,
            'entities_id': getWorkerEntityId(),
        });
        const tab = 'Glpi\\Form\\ServiceCatalog\\ServiceCatalog$1';
        await page.goto(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

        // Make sure the values we are about to apply are are not already set to
        // prevent false negative.
        const description = await glpi_page.initRichTextByLabel("Description");
        await expect(description).not.toContainText('My description');
        const category_dropdown = glpi_page.getDropdownByLabel("Category");
        await expect(category_dropdown).not.toHaveText(category_name);

        // Set values
        await description.fill('My description');
        // The option is prefixed by a "»" in the tree dropdown but the selected
        // value is displayed without it, so the dropdown content is checked
        // separately.
        await glpi_page.doSetDropdownValue(
            category_dropdown,
            category_dropdown_value,
            true,
            false
        );
        const pin_checkbox = glpi_page.getCheckbox(
            'Pin to top of the service catalog'
        );
        await pin_checkbox.check();

        // Save changes
        await doSaveChanges(page, glpi_page);

        // Validate values
        await expect(
            await glpi_page.initRichTextByLabel("Description")
        ).toContainText('My description');
        await expect(glpi_page.getDropdownByLabel("Category"))
            .toHaveText(category_name)
        ;
        await expect(
            glpi_page.getCheckbox('Pin to top of the service catalog')
        ).toBeChecked();

        // Note: picking an illustration is not validated here as it is already
        // done in the illustration_picker.spec.ts test.
    });

    test('can configure service catalog for KnowbaseItem', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const category_name = await setupCategory(api);
        // GLPI add "»" prefix to common tree dropdown values
        const category_dropdown_value = `»${category_name}`;

        // `answer` and not `content`: `glpi_knowbaseitems` has no `content`
        // column, so the cypress version silently created the item with a NULL
        // answer. GLPI's knowledge base search then crashes on it
        // (`RichText::getTextFromHtml(null)` in `KnowbaseItemController`),
        // which breaks every later search hitting this row, in this run and in
        // all the next ones.
        const knowbase_item_id = await api.createItem('KnowbaseItem', {
            'name': `Test knowbase item for service_catalog_tab.cy.js ${randomUUID()}`,
            'answer': "My content",
            'entities_id': getWorkerEntityId(),
        });
        const tab = 'Glpi\\Form\\ServiceCatalog\\ServiceCatalog$1';
        await page.goto(
            `/front/knowbaseitem.form.php?id=${knowbase_item_id}&forcetab=${tab}`
        );

        // Check that the service catalog configuration isn't active by default
        await expect(glpi_page.getCheckbox('Active')).not.toBeChecked();

        // Verify that content is not interactable when toggle is disabled
        // eslint-disable-next-line playwright/no-raw-locators
        const config = page.locator('[data-service-catalog-config]');
        await expect(config).toHaveCSS('pointer-events', 'none');
        await expect(config).toHaveCSS('opacity', '0.5');

        // Set values
        await glpi_page.getCheckbox('Active').check();

        // Verify that content becomes interactable when toggle is enabled
        await expect(config).toHaveCSS('pointer-events', 'auto');
        await expect(config).toHaveCSS('opacity', '1');

        const description = await glpi_page.initRichTextByLabel("Description");
        await description.fill('My description');
        await glpi_page.doSetDropdownValue(
            glpi_page.getDropdownByLabel("Category"),
            category_dropdown_value,
            true,
            false
        );
        await glpi_page.getCheckbox('Pin to top of the service catalog').check();

        // Save changes
        await doSaveChanges(page, glpi_page);

        // Validate values
        await expect(glpi_page.getCheckbox('Active')).toBeChecked();
        await expect(
            await glpi_page.initRichTextByLabel("Description")
        ).toContainText('My description');
        await expect(glpi_page.getDropdownByLabel("Category"))
            .toHaveText(category_name)
        ;
        await expect(
            glpi_page.getCheckbox('Pin to top of the service catalog')
        ).toBeChecked();
    });
});
