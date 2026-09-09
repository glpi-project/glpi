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
import { Locator } from '@playwright/test';
import { randomUUID } from 'crypto';
import { expect, test } from '../../../fixtures/glpi_fixture';
import { FormPage } from '../../../pages/FormPage';
import { Api } from '../../../utils/Api';
import { Profiles } from '../../../utils/Profiles';
import { getWorkerEntityId } from '../../../utils/WorkerEntities';

test.describe('Dropdown item form question type', () => {
    type Fixtures = {
        uuid: string,
        form_id: number,
        subcategory_id: number,
    };

    const setupForm = async (form: FormPage, api: Api): Promise<Fixtures> => {
        const uuid = randomUUID();

        const form_id = await api.createItem('Glpi\\Form\\Form', {
            'name'       : `Tests form for the dropdown item form question type suite ${uuid}`,
            'is_active'  : true,
            'entities_id': getWorkerEntityId(),
        });

        const category_id = await api.createItem('ITILCategory', {
            'name'       : `Root category ${uuid}`,
            'entities_id': getWorkerEntityId(),
        });
        const subcategory_id = await api.createItem('ITILCategory', {
            'name'             : `Subroot category ${uuid}`,
            'itilcategories_id': category_id,
            'entities_id'      : getWorkerEntityId(),
        });
        await api.createItem('ITILCategory', {
            'name'             : `Subsubroot category ${uuid}`,
            'itilcategories_id': subcategory_id,
            'entities_id'      : getWorkerEntityId(),
        });

        await form.goto(form_id);

        // Add a question
        const question = await form.addQuestion('Test dropdown item question');

        // Change question type
        await form.setQuestionType(question, "Item");

        // Change the question sub-type to Dropdowns
        await form.setSubQuestionType(question, "Dropdowns");

        // Select the ITIL Category itemtype
        await form.doSetDropdownValue(
            form.getDropdownByLabel('Select a dropdown type', question)
                .filter({ visible: true }),
            "ITIL categories",
            false
        );

        return { uuid, form_id, subcategory_id };
    };

    const getAdvancedConfigurationMenu = (form: FormPage): Locator => {
        return form.page.getByRole('menu', { name: 'Advanced configuration' });
    };

    const getSelectedValues = async (select: Locator): Promise<string[]> => {
        return await select.evaluate(
            (el: HTMLSelectElement) => Array.from(el.selectedOptions)
                .map((option) => option.value)
        );
    };

    // The end user dropdown loads its values through ajax, so the value must be
    // searched instead of being looked for in the initial list of options.
    const assertDropdownHasValue = async (
        form: FormPage,
        dropdown: Locator,
        value: string,
        should_exist: boolean,
    ): Promise<void> => {
        // The search results replace the content of the dropdown: they must be
        // awaited, otherwise a `should_exist: false` assertion would be
        // satisfied by the still loading dropdown. Select2 debounces the
        // search and may not have received the whole term when it fires the
        // request, so any non empty term is accepted: it is a substring of the
        // searched value, thus the value would be listed if it existed.
        const search_response = form.page.waitForResponse((response) =>
            response.url().includes('/ajax/getDropdownValue.php')
            && (response.request().postDataJSON()?.searchText ?? '') !== ''
        );

        await dropdown.click();
        await form.page.keyboard.type(value);
        await search_response;

        // A later search may still be in flight, wait for the results to be
        // fully loaded.
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(form.page.locator('.loading-results')).toHaveCount(0);

        // Sub entities values are prefixed by a "»" in the tree dropdowns
        const option = form.page.getByRole('listbox')
            .getByRole('option', { name: value, exact: true })
        ;
        const option_with_prefix = form.page.getByRole('listbox')
            .getByRole('option', { name: `»${value}`, exact: true })
        ;
        await expect(option.or(option_with_prefix))
            .toHaveCount(should_exist ? 1 : 0)
        ;
        await form.page.keyboard.press('Escape');
    };

    test('can open advanced configuration dropdown', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Open the advanced configuration dropdown
        await form.getButton('Advanced configuration').click();

        // Check that the dropdown is open
        const menu = getAdvancedConfigurationMenu(form);
        await expect(menu).toBeVisible();

        // Check that the dropdown contains the expected fields
        await expect(
            menu.getByLabel('Filter ticket categories', { exact: true })
        ).toBeAttached();
        await expect(
            menu.getByLabel('Subtree root', { exact: true })
        ).toBeAttached();
        await expect(
            menu.getByLabel('Limit subtree depth', { exact: true })
        ).toBeAttached();
    });

    test('can set advanced configuration fields', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const { uuid, subcategory_id } = await setupForm(form, api);

        // Open the advanced configuration dropdown
        await form.getButton('Advanced configuration').click();

        const menu = getAdvancedConfigurationMenu(form);

        // Check default values of the fields
        expect(
            await getSelectedValues(
                menu.getByLabel('Filter ticket categories', { exact: true })
            )
        ).toEqual(['request', 'incident', 'change', 'problem']);

        // Set the filter ticket categories field
        // This unselects the value, so the dropdown content must not be checked
        await form.doSetDropdownValue(
            form.getDropdownByLabel('Filter ticket categories', menu),
            'Request categories',
            true,
            false
        );

        // Set the subtree root field
        await form.doSearchAndClickDropdownValue(
            form.getDropdownByLabel('Subtree root', menu),
            `Subroot category ${uuid}`
        );

        // Set the limit subtree depth field
        await menu.getByLabel('Limit subtree depth', { exact: true }).fill('3');

        // Save form and reload
        await form.doSaveFormEditorAndReload();

        // Focus question
        await form.getNthQuestion(0).click();

        // Open the advanced configuration dropdown again
        await form.getButton('Advanced configuration').click();

        // Check that the fields have been set correctly
        const reloaded_menu = getAdvancedConfigurationMenu(form);
        expect(
            await getSelectedValues(
                reloaded_menu.getByLabel('Filter ticket categories', {
                    exact: true,
                })
            )
        ).toEqual(['incident', 'change', 'problem']);
        await expect(
            reloaded_menu.getByLabel('Subtree root', { exact: true })
        ).toHaveValue(String(subcategory_id));
        await expect(
            reloaded_menu.getByLabel('Limit subtree depth', { exact: true })
        ).toHaveValue('3');
    });

    test('"Filter ticket categories" field can only displayed for ITIL categories', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Open the advanced configuration dropdown
        await form.getButton('Advanced configuration').click();

        // Check that the "Filter ticket categories" field is visible
        await expect(
            getAdvancedConfigurationMenu(form)
                .getByLabel('Filter ticket categories', { exact: true })
        ).toBeVisible();

        // Change the question sub-type to "Dropdowns"
        await form.doSetDropdownValue(
            form.getDropdownByLabel('Select a dropdown type')
                .filter({ visible: true }),
            "Locations",
            false
        );

        // Open the advanced configuration dropdown
        await form.getButton('Advanced configuration').click();

        // Check that the "Filter ticket categories" field is visible
        await expect(
            getAdvancedConfigurationMenu(form)
                .getByLabel('Filter ticket categories', { exact: true })
        ).toBeHidden();
    });

    test('only "Visible in the simplified interface" ITIL categories are displayed in self service interface', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const { uuid, form_id } = await setupForm(form, api);

        // Create a new ITIL category that is not visible in the simplified interface
        await api.createItem('ITILCategory', {
            'name'              : `Hidden for self service category ${uuid}`,
            'is_helpdeskvisible': false,
            'entities_id'       : getWorkerEntityId(),
        });

        // Create a new ITIL category that is visible in the simplified interface
        await api.createItem('ITILCategory', {
            'name'              : `Visible in self service category ${uuid}`,
            'is_helpdeskvisible': true,
            'entities_id'       : getWorkerEntityId(),
        });

        // Save form
        await form.doSaveFormEditor();

        try {
            // Render the form in self service interface
            await profile.set(Profiles.SelfService);
            await page.goto(`/Form/Render/${form_id}`);

            // Check that the dropdown contains only the visible ITIL categories
            await assertDropdownHasValue(
                form,
                form.getDropdownByLabel('Test dropdown item question'),
                `Hidden for self service category ${uuid}`,
                false
            );
            await assertDropdownHasValue(
                form,
                form.getDropdownByLabel('Test dropdown item question'),
                `Visible in self service category ${uuid}`,
                true
            );
        } finally {
            // Change back to Super-Admin profile
            await profile.set(Profiles.SuperAdmin);
        }
        await page.reload();

        // Check that the dropdown contains both ITIL categories in the form
        await assertDropdownHasValue(
            form,
            form.getDropdownByLabel('Test dropdown item question'),
            `Hidden for self service category ${uuid}`,
            true
        );
        await assertDropdownHasValue(
            form,
            form.getDropdownByLabel('Test dropdown item question'),
            `Visible in self service category ${uuid}`,
            true
        );
    });
});
