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
import { test, expect } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { ServiceCatalogPage } from '../../pages/ServiceCatalogPage';

function createActiveForm(api: import('../../utils/Api').Api, name: string, entity_id: number, options: Record<string, unknown> = {})
{
    return api.createItem('Glpi\\Form\\Form', {
        'name': name,
        'is_active': true,
        'entities_id': entity_id,
        ...options,
    });
}

function createCategory(api: import('../../utils/Api').Api, name: string, options: Record<string, unknown> = {})
{
    return api.createItem('Glpi\\Form\\Category', {
        'name': name,
        ...options,
    });
}

function createKnowledgeBaseItem(api: import('../../utils/Api').Api, name: string, entity_id: number, options: Record<string, unknown> = {})
{
    return api.createItem('KnowbaseItem', {
        'name': name,
        'answer': `Content for ${name}`,
        'description': `Description for ${name}`,
        'is_faq': 1,
        'show_in_service_catalog': 1,
        '_visibility': {
            '_type': 'Entity',
            'entities_id': entity_id,
            'is_recursive': 1,
        },
        ...options,
    });
}

test.describe('Service Catalog Page', () => {
    test.afterEach(async ({profile, api}) => {
        // Ensure we reset `expand_service_catalog` to false after tests that set it to true
        await profile.set(Profiles.SuperAdmin);
        await api.updateItem('Entity', getWorkerEntityId(), {'expand_service_catalog': false});
    });

    test(`Can filter and go to a form using the service catalog`, async ({page, profile}) => {
        // Go to the service catalog
        await profile.set(Profiles.SelfService);
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();

        // Search and go to a specific form
        await service_catalog.doSearchItem('Request a service');
        await service_catalog.doGoToItem('Request a service');
        await expect(page).toHaveURL('/Form/Render/2');
    });

    test(`Can filter and go to a KB item using the service catalog`, async ({page, profile, api}) => {
        // Create a KB entry
        const kb = `My KB entry ${randomUUID()}`;
        await createKnowledgeBaseItem(api, kb, getWorkerEntityId());

        // Go to the service catalog
        await profile.set(Profiles.SelfService);
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();

        // Search and go to a specific KB
        await service_catalog.doSearchItem(kb);
        await service_catalog.doGoToItem(kb);
        await expect(page).toHaveURL(/\/front\/helpdesk.faq.php/);
    });

    test(`Search with no results`, async ({page, profile}) => {
        // Go to the service catalog
        await profile.set(Profiles.SelfService);
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();

        // Make an impossible search
        await service_catalog.doSearchItem("AAAAAAAAAAAAAAAAAA");
        await expect(page.getByText('No forms found')).toBeVisible();
    });

    test(`Can use the service catalog on the central interface`, async ({page, profile, api}) => {
        const uuid = randomUUID();
        const form_name = `Test form for service_catalog_page ${uuid}`;

        await profile.set(Profiles.SuperAdmin);

        // Create a simple active form
        const form_id = await createActiveForm(api, form_name, getWorkerEntityId(), {
            'description': 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.',
        });

        // Add a question via the form editor UI
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\Form\\Form$main`
        );
        await page.getByRole('button', {name: 'Add a question'}).click();
        await page.keyboard.type('Question 1');
        await page.getByRole('button', {name: 'Save', exact: true}).click();
        await expect(page.getByRole('alert')).toContainText('Item successfully updated');

        // Go to service catalog
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();

        // Validate breadcrumbs and menu
        await service_catalog.assertBannerBreadcrumbs();
        await service_catalog.assertServiceCatalogMenuActive();

        // Find and go to our form
        await service_catalog.doSearchItem(uuid);
        await service_catalog.doGoToItem(form_name);
        await expect(page).toHaveURL(/\/Form\/Render/);

        // Validate breadcrumbs and menu are still correct
        await service_catalog.assertBannerBreadcrumbs();
        await service_catalog.assertServiceCatalogMenuActive();

        // Submit the form
        await page.getByRole('textbox', {name: 'Question 1'}).fill('Answer 1');
        await page.getByRole('button', {name: 'Submit'}).click();
        await expect(page.getByRole('alert')).toContainText('Item successfully created');
    });

    test(`Can display service catalog with form that has no description`, async ({page, profile, api}) => {
        const uuid = randomUUID();
        const form_name = `Test form without description ${uuid}`;

        await profile.set(Profiles.SuperAdmin);
        await createActiveForm(api, form_name, getWorkerEntityId());

        await profile.set(Profiles.SelfService);
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();
        await service_catalog.doSearchItem(form_name);

        const form_region = service_catalog.getItemRegion(form_name);
        await expect(form_region).toBeVisible();
        await expect(form_region.getByRole('heading', {name: form_name})).toBeVisible();
        const description_text = await form_region.getByTestId('service-catalog-description').textContent();
        expect(description_text?.trim()).toBe('');
    });

    test(`Can change sort order in the service catalog`, async ({page, profile, api}) => {
        const uuid = randomUUID();

        await profile.set(Profiles.SuperAdmin);

        // Create forms with different names
        await createActiveForm(api, `A form ${uuid}`, getWorkerEntityId(), {
            'description': 'Lorem ipsum dolor sit amet.',
        });
        await createActiveForm(api, `C form ${uuid}`, getWorkerEntityId(), {
            'description': 'Lorem ipsum dolor sit amet.',
        });
        const b_form_id = await createActiveForm(api, `B form ${uuid}`, getWorkerEntityId(), {
            'description': 'Lorem ipsum dolor sit amet.',
        });

        // Add a question to B form so it can be submitted
        const b_form_sections = await api.getSubItems(
            'Glpi\\Form\\Form', b_form_id, 'Glpi\\Form\\Section'
        );
        await api.createItem('Glpi\\Form\\Question', {
            'name': 'Question 1',
            'type': 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
            'vertical_rank': 0,
            'forms_sections_id': b_form_sections[0].id,
        });

        // Visit and submit B form as Self-Service to increment its popularity
        await profile.set(Profiles.SelfService);
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();
        await service_catalog.doSearchItem(uuid);
        await service_catalog.doGoToItem(`B form ${uuid}`);
        await page.getByRole('button', {name: 'Submit'}).click();
        await expect(page.getByRole('alert')).toContainText('Item successfully created');

        // Return to service catalog and search
        await service_catalog.goto();
        await service_catalog.doSearchItem(uuid);

        // Check the default sort order (most popular: B first, then alphabetical)
        await expect(page.getByRole('textbox', {name: 'Most popular'})).toBeVisible();
        const links = service_catalog.getFormsRegion().getByRole('link');
        await expect(links).toHaveCount(3);
        await expect(links.nth(0).getByRole('heading')).toContainText(`B form ${uuid}`);
        await expect(links.nth(1).getByRole('heading')).toContainText(`A form ${uuid}`);
        await expect(links.nth(2).getByRole('heading')).toContainText(`C form ${uuid}`);

        // Change sort order to "Alphabetical"
        await service_catalog.doChangeSortOrder('Alphabetical');

        await expect(page.getByRole('textbox', {name: 'Alphabetical'})).toBeVisible();
        await expect(links.nth(0).getByRole('heading')).toContainText(`A form ${uuid}`);
        await expect(links.nth(1).getByRole('heading')).toContainText(`B form ${uuid}`);
        await expect(links.nth(2).getByRole('heading')).toContainText(`C form ${uuid}`);

        // Change sort order to "Reverse alphabetical"
        await service_catalog.doChangeSortOrder('Reverse alphabetical');

        await expect(page.getByRole('textbox', {name: 'Reverse alphabetical'})).toBeVisible();
        await expect(links.nth(0).getByRole('heading')).toContainText(`C form ${uuid}`);
        await expect(links.nth(1).getByRole('heading')).toContainText(`B form ${uuid}`);
        await expect(links.nth(2).getByRole('heading')).toContainText(`A form ${uuid}`);
    });

    test(`Deleted forms are not displayed in the service catalog`, async ({page, profile, api}) => {
        const uuid = randomUUID();
        const form_name = `Test form deleted ${uuid}`;

        await profile.set(Profiles.SuperAdmin);
        const form_id = await createActiveForm(api, form_name, getWorkerEntityId());

        // Verify form is visible in service catalog
        await profile.set(Profiles.SelfService);
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();
        await service_catalog.doSearchItem(form_name);
        await expect(service_catalog.getItemRegion(form_name)).toBeVisible();

        // Put the form in trashbin
        await profile.set(Profiles.SuperAdmin);
        await page.goto(`/front/form/form.form.php?id=${form_id}`);
        await page.getByRole('button', {name: 'Put in trashbin'}).click();
        await expect(page.getByRole('alert')).toContainText('Item successfully deleted');

        // Verify form is no longer visible
        await profile.set(Profiles.SelfService);
        await service_catalog.goto();
        await service_catalog.doSearchItem(form_name);
        await expect(service_catalog.getItemRegion(form_name)).toBeHidden();
        await expect(service_catalog.getFormsRegion()).toContainText('No forms found');

        // Purge the form
        await profile.set(Profiles.SuperAdmin);
        await page.goto(`/front/form/form.form.php?id=${form_id}`);
        await page.getByRole('button', {name: 'Delete permanently'}).click();
        await expect(page.getByRole('alert')).toContainText('Item successfully purged');

        // Verify form is still not visible after purge
        await profile.set(Profiles.SelfService);
        await service_catalog.goto();
        await service_catalog.doSearchItem(form_name);
        await expect(service_catalog.getItemRegion(form_name)).toBeHidden();
        await expect(service_catalog.getFormsRegion()).toContainText('No forms found');
    });

    test(`Can filter forms, KB items and categories nested in category`, async ({page, profile, api}) => {
        const uuid = randomUUID();

        await profile.set(Profiles.SuperAdmin);

        // Create categories, form and KB item
        const category_id = await createCategory(api, `Root Category ${uuid}`);
        const sub_category_id = await createCategory(api, `Nested category ${uuid}`, {
            'forms_categories_id': category_id,
        });
        await createActiveForm(api, `Nested form for ${sub_category_id}`, getWorkerEntityId(), {
            'forms_categories_id': sub_category_id,
        });
        await createActiveForm(api, `Nested form ${uuid}`, getWorkerEntityId(), {
            'forms_categories_id': category_id,
        });
        await createKnowledgeBaseItem(api, `Nested KB item ${uuid}`, getWorkerEntityId(), {
            'forms_categories_id': category_id,
        });

        // Visit service catalog and apply filter
        await profile.set(Profiles.SelfService);
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();
        await service_catalog.doSearchItem(`Nested ${uuid}`);

        // Assert correct items are displayed
        await expect(service_catalog.getCategoryRegion(`Nested category ${uuid}`)).toBeVisible();
        await expect(service_catalog.getItemRegion(`Nested form ${uuid}`)).toBeVisible();
        await expect(service_catalog.getItemRegion(`Nested KB item ${uuid}`)).toBeVisible();
    });

    test(`Can pick a category in the expanded service catalog`, async ({page, profile, api}) => {
        const root_category_name = `Root category: ${randomUUID()}`;
        const child_category_name = `Child category: ${randomUUID()}`;
        const form_name = `Test form for service_catalog_page.cy.js ${randomUUID()}`;

        await profile.set(Profiles.SuperAdmin);

        // Enable expanded service catalog
        await api.updateItem('Entity', getWorkerEntityId(), {'expand_service_catalog': true});

        // Create items
        const root_category_id = await createCategory(api, root_category_name, {
            'description': "Root category description.",
        });
        const child_category_id = await createCategory(api, child_category_name, {
            'description': "Child category description.",
            'forms_categories_id': root_category_id,
        });
        await createActiveForm(api, form_name, getWorkerEntityId(), {
            'description': "Form description.",
            'forms_categories_id': child_category_id,
        });

        // Go to the service catalog
        await profile.set(Profiles.SelfService);
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();

        // Search and ensure root category is visible
        await service_catalog.doSearchItem(root_category_name);

        await expect(page.getByRole('region', {name: root_category_name})).toBeVisible();
        await expect(page.getByText('Root category description.')).toBeVisible();

        // Ensure child category is visible and go to it
        const child_category = page.getByRole('region', {name: child_category_name});
        await expect(child_category).toBeVisible();
        await expect(page.getByText('Child category description.')).toBeVisible();

        // Ensure form isn't visible before clicking on the child category
        await expect(page.getByRole('link', {name: form_name})).toBeHidden();
        await child_category.click();
        await expect(page.getByRole('link', {name: form_name})).toBeVisible();
    });
});

test.describe('Service Catalog Page - Isolated', () => {
    let entity_id: number;

    test.beforeEach(async ({api, profile, entity}) => {
        await profile.set(Profiles.SuperAdmin);

        // Create a new entity
        entity_id = await api.createItem('Entity', {
            'name': `Entity for service catalog tests ${randomUUID()}`,
            'expand_service_catalog': true,
            'entities_id': 1, // Child of the root E2E entity
        });

        // Switch to the new entity
        await entity.switchToWithRecursion(entity_id);
        api.refreshSession();
    });

    test.afterEach(async ({api, profile, entity}) => {
        await profile.set(Profiles.SuperAdmin);

        // Switch back to the worker entity
        await entity.switchToWithoutRecursion(getWorkerEntityId());
        api.refreshSession();
    });

    test(`Can categorize knowledge base items in the expanded service catalog`, async ({page, profile, api, entity}) => {
        const uuid = randomUUID();
        const category_name = `KB Category ${uuid}`;
        const kb_name_1 = `KB in category ${uuid}`;
        const kb_name_2 = `KB at root ${uuid}`;
        const kb_name_3 = `KB in a nested category ${uuid}`;

        // Create items
        const category_id = await createCategory(api, category_name, {
            'description': "Category for KB items",
        });
        await createKnowledgeBaseItem(api, kb_name_1, entity_id, {
            'forms_categories_id': category_id,
        });
        await createKnowledgeBaseItem(api, kb_name_2, entity_id);
        const nested_category_id = await createCategory(api, `Nested ${category_name}`, {
            'description': "Category for KB items",
            'forms_categories_id': category_id,
        });
        await createKnowledgeBaseItem(api, kb_name_3, entity_id, {
            'forms_categories_id': nested_category_id,
        });

        // Go to the service catalog
        await profile.set(Profiles.SelfService);
        await entity.switchToWithoutRecursion(entity_id);
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();

        // Root KB item should be visible
        await expect(service_catalog.getItemRegion(kb_name_2)).toBeVisible();
        // Category should be visible
        await expect(service_catalog.getCategoryRegion(category_name)).toBeVisible();
        // Categorized KB item should be visible at root
        await expect(service_catalog.getCategoryRegion(category_name).getByRole('link', { name: kb_name_1 })).toBeVisible();
        // Nested category should be visible
        await expect(service_catalog.getLink(`Nested ${category_name}`)).toBeVisible();
        // Categorized KB item should not be visible here
        await expect(service_catalog.getLink(kb_name_3)).toBeHidden();

        // Navigate to nested category
        const items_response_promise = service_catalog.waitForItemsResponse();
        await service_catalog.doGoToItem(`Nested ${category_name}`);
        await items_response_promise;

        // Now the categorized KB item should be visible
        await expect(service_catalog.getItemRegion(kb_name_3)).toBeVisible();

        // But other items should not be visible
        await expect(service_catalog.getItemRegion(kb_name_1)).toBeHidden();
        await expect(service_catalog.getItemRegion(kb_name_2)).toBeHidden();
    });

    test(`Can navigate through the expanded service catalog using the breadcrumbs`, async ({page, profile, api, entity}) => {
        const uuid = randomUUID();

        // Create a category tree with 3 levels and a form in the deepest category
        const root_category_id = await createCategory(api, `Root Category ${uuid}`, {
            'description': `Root Category ${uuid} description.`,
        });
        const child_category_1_id = await createCategory(api, `Child Category 1 ${uuid}`, {
            'description': `Child Category 1 ${uuid} description.`,
            'forms_categories_id': root_category_id,
        });
        const child_category_2_id = await createCategory(api, `Child Category 2 ${uuid}`, {
            'description': `Child Category 2 ${uuid} description.`,
            'forms_categories_id': child_category_1_id,
        });
        await createActiveForm(api, 'Form 1', entity_id, {
            'forms_categories_id': child_category_2_id,
        });

        // Go to the service catalog
        await profile.set(Profiles.SelfService);
        await entity.switchToWithoutRecursion(entity_id);
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();

        // Filter to show only items created in this test
        await service_catalog.doSearchItem(uuid);

        // Check breadcrumb at root level
        const breadcrumb_nav = service_catalog.getCategoryBreadcrumbNav();
        await expect(breadcrumb_nav.getByRole('link', {name: 'Service catalog'})).toBeHidden();
        await expect(breadcrumb_nav.getByRole('link', {name: `Root Category ${uuid}`})).toBeHidden();
        await expect(breadcrumb_nav.getByRole('link', {name: `Child Category 1 ${uuid}`})).toBeHidden();
        await expect(breadcrumb_nav.getByRole('link', {name: `Child Category 2 ${uuid}`})).toBeHidden();

        // Navigate into Child Category 1 via the Root Category region
        const root_region = page.getByRole('region', {name: `Root Category ${uuid}`, exact: true});
        const items_response = service_catalog.waitForItemsResponse();
        await root_region.getByRole('link', {name: `Child Category 1 ${uuid}`}).click();
        await items_response;

        // Check breadcrumb shows Child Category 1
        await expect(breadcrumb_nav.getByRole('link', {name: 'Service catalog'})).toBeVisible();
        await expect(breadcrumb_nav.getByRole('link', {name: `Root Category ${uuid}`})).toBeVisible();
        await expect(breadcrumb_nav.getByRole('link', {name: `Child Category 1 ${uuid}`})).toBeHidden();
        await expect(breadcrumb_nav.getByRole('link', {name: `Child Category 2 ${uuid}`})).toBeHidden();

        // Check that the form is visible inside Child Category 2
        const child_2_region = page.getByRole('region', {name: `Child Category 2 ${uuid}`, exact: true});
        await expect(child_2_region.getByRole('link', {name: 'Form 1'})).toBeVisible();

        // Go back to root via "Service catalog" breadcrumb link
        const back_response = service_catalog.waitForItemsResponse();
        await page.getByRole('main').getByRole('link', {name: 'Service catalog', exact: true}).click();
        await back_response;

        // Check breadcrumb is back to root state
        await expect(breadcrumb_nav.getByRole('link', {name: 'Service catalog'})).toBeHidden();
        await expect(breadcrumb_nav.getByRole('link', {name: `Root Category ${uuid}`})).toBeHidden();
        await expect(breadcrumb_nav.getByRole('link', {name: `Child Category 1 ${uuid}`})).toBeHidden();
        await expect(breadcrumb_nav.getByRole('link', {name: `Child Category 2 ${uuid}`})).toBeHidden();
    });

    test(`Can paginate through the service catalog`, async ({page, profile, api, entity}) => {
        const uuid = randomUUID();
        const forms_per_page = 12; // Default value from ServiceCatalogManager::ITEMS_PER_PAGE
        const total_forms = forms_per_page + 5;

        // Create enough forms to trigger pagination
        const form_promises = [];
        for (let i = 0; i < total_forms; i++) {
            form_promises.push(createActiveForm(
                api,
                `Form ${String.fromCharCode(65 + i)} ${uuid}`,
                entity_id,
            ));
        }
        await Promise.all(form_promises);

        // Go to service catalog
        await profile.set(Profiles.SelfService);
        await entity.switchToWithoutRecursion(entity_id);
        const service_catalog = new ServiceCatalogPage(page);
        await service_catalog.goto();
        await service_catalog.doSearchItem(uuid);

        // Verify first page content
        for (let i = 0; i < forms_per_page; i++) {
            await expect(service_catalog.getItemRegion(`Form ${String.fromCharCode(65 + i)} ${uuid}`)).toBeVisible();
        }
        // Verify 13th form is not visible
        await expect(service_catalog.getItemRegion(`Form ${String.fromCharCode(65 + forms_per_page)} ${uuid}`)).toBeHidden();

        // Test pagination controls on first page
        await service_catalog.assertPaginationState({
            activePage: '1',
            visiblePages: ['2'],
            hiddenPages: ['3'],
            disabledButtons: ['Previous page', 'First page'],
            enabledButtons: ['Next page', 'Last page'],
        });

        // Go to second page
        await service_catalog.doGoToPaginationPage('2');

        // Verify second page content
        for (let i = 0; i < forms_per_page; i++) {
            await expect(service_catalog.getItemRegion(`Form ${String.fromCharCode(65 + i)} ${uuid}`)).toBeHidden();
        }
        for (let i = forms_per_page; i < total_forms; i++) {
            await expect(service_catalog.getItemRegion(`Form ${String.fromCharCode(65 + i)} ${uuid}`)).toBeVisible();
        }

        // Test pagination controls on second (last) page
        await service_catalog.assertPaginationState({
            activePage: '2',
            visiblePages: ['1'],
            hiddenPages: ['3'],
            disabledButtons: ['Next page', 'Last page'],
            enabledButtons: ['Previous page', 'First page'],
        });

        // Go back to first page using Previous page button
        await service_catalog.doGoToPaginationPage('Previous page');

        // Verify first page content again
        for (let i = 0; i < forms_per_page; i++) {
            await expect(service_catalog.getItemRegion(`Form ${String.fromCharCode(65 + i)} ${uuid}`)).toBeVisible();
        }
        await expect(service_catalog.getItemRegion(`Form ${String.fromCharCode(65 + forms_per_page)} ${uuid}`)).toBeHidden();
    });
});
