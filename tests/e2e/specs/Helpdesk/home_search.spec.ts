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
import { expect, test } from '../../fixtures/glpi_fixture';
import { Api } from '../../utils/Api';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe('Helpdesk Search with FormProvider', () => {
    // Search token, unique per test. Dashes are stripped to keep it a single
    // searchable word, like the `Date.now()` of the cypress version.
    const getUniqueId = (): string => randomUUID().replaceAll('-', '');

    const createActiveForm = async (
        api: Api,
        name: string,
        category: number = 0,
        description: string = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.',
    ): Promise<number> => {
        return await api.createItem('Glpi\\Form\\Form', {
            'name': name,
            'description': description,
            'is_active': true,
            'forms_categories_id': category,
            'entities_id': getWorkerEntityId(),
        });
    };

    const createKnowledgeBaseItem = async (
        api: Api,
        name: string,
        options: object = {},
    ): Promise<number> => {
        const defaults = {
            'name': name,
            'answer': `Content for ${name}`,
            'description': `Description for ${name}`,
            'is_faq': 1,
            'show_in_service_catalog': 1,
            'forms_categories_id': 0, // Root by default
            'is_pinned': 0,
            'entities_id': getWorkerEntityId(),
            '_visibility': {
                '_type': 'Entity',
                'entities_id': getWorkerEntityId(),
                'is_recursive': 0,
            },
        };

        return await api.createItem('KnowbaseItem', { ...defaults, ...options });
    };

    const getSearchInput = (page: Page) => {
        return page.getByPlaceholder(
            'Search for knowledge base entries or forms'
        );
    };

    test('can search forms without categories using FormProvider', async ({
        page,
        profile,
        api,
    }) => {
        const unique_id = getUniqueId();
        const form_name_1 = `Form No Category 1 ${unique_id}`;
        const form_name_2 = `Form No Category 2 ${unique_id}`;

        await profile.set(Profiles.SuperAdmin);

        // Create forms without categories (forms_categories_id = 0)
        await createActiveForm(
            api,
            form_name_1,
            0,
            'Form without any category assigned'
        );
        await createActiveForm(
            api,
            form_name_2,
            0,
            'Another form without category'
        );

        await profile.set(Profiles.SelfService);
        await page.goto('/Helpdesk');

        // Both forms should be visible when searching
        await getSearchInput(page).fill(unique_id);

        // Wait for search results and verify both forms appear
        await expect(page.getByRole('link', { name: form_name_1 }))
            .toBeAttached()
        ;
        await expect(page.getByRole('link', { name: form_name_2 }))
            .toBeAttached()
        ;
    });

    test('can search forms with and without categories using FormProvider', async ({
        page,
        profile,
        api,
    }) => {
        const unique_id = getUniqueId();
        const category_name = `Test Category ${unique_id}`;
        const form_with_category = `Form With Category ${unique_id}`;
        const form_without_category = `Form Without Category ${unique_id}`;

        await profile.set(Profiles.SuperAdmin);

        // Create a category
        const category_id = await api.createItem('Glpi\\Form\\Category', {
            'name': category_name,
            'description': "Category for testing FormProvider",
        });

        // Create form with category
        await createActiveForm(
            api,
            form_with_category,
            category_id,
            'Form that has a category'
        );

        // Create form without category
        await createActiveForm(
            api,
            form_without_category,
            0,
            'Form that has no category'
        );

        try {
            await profile.set(Profiles.SelfService);
            await page.goto('/Helpdesk');

            // Search for forms - both should appear
            await getSearchInput(page).fill(unique_id);

            // Verify both forms appear (testing FormProvider correctly handles null categories)
            await expect(page.getByRole('link', { name: form_with_category }))
                .toBeAttached()
            ;
            await expect(
                page.getByRole('link', { name: form_without_category })
            ).toBeAttached();

            // Test specific filtering
            await getSearchInput(page).fill('Without Category');

            await expect(
                page.getByRole('link', { name: form_without_category })
            ).toBeAttached();
            await expect(page.getByRole('link', { name: form_with_category }))
                .not.toBeAttached()
            ;
        } finally {
            // `Glpi\Form\Category` has no entity, so it is visible to every
            // worker: purge it to keep the shared list small.
            await api.purgeItem('Glpi\\Form\\Category', category_id);
        }
    });

    test('verifies FormProvider fuzzy matching works correctly', async ({
        page,
        profile,
        api,
    }) => {
        const unique_id = getUniqueId();
        const form_name = `Hardware Request Form ${unique_id}`;

        await profile.set(Profiles.SuperAdmin);
        await createActiveForm(
            api,
            form_name,
            0,
            'Request for computer equipment and hardware'
        );

        await profile.set(Profiles.SelfService);
        await page.goto('/Helpdesk');

        // Test fuzzy matching on name
        await getSearchInput(page).fill('hardware');
        await expect(page.getByRole('link', { name: form_name }))
            .toBeAttached()
        ;

        // Test fuzzy matching on description
        await getSearchInput(page).fill('computer');
        await expect(page.getByRole('link', { name: form_name }))
            .toBeAttached()
        ;

        // Test non-matching filter
        await getSearchInput(page).fill('nonexistent');
        await expect(page.getByRole('link', { name: form_name }))
            .not.toBeAttached()
        ;
    });

    test('verifies pinned forms always appear regardless of filter', async ({
        page,
        profile,
        api,
    }) => {
        const unique_id = getUniqueId();
        const pinned_form = `Important Pinned Form ${unique_id}`;
        const regular_form = `Regular Form ${unique_id}`;

        await profile.set(Profiles.SuperAdmin);

        // Create a pinned form
        const pinned_form_id = await api.createItem('Glpi\\Form\\Form', {
            'name': pinned_form,
            'description': 'This is a pinned form',
            'is_active': true,
            'is_pinned': true,
            'forms_categories_id': 0,
            'entities_id': getWorkerEntityId(),
        });

        // Create a regular form
        await createActiveForm(api, regular_form, 0, 'This is a regular form');

        try {
            await profile.set(Profiles.SelfService);
            await page.goto('/Helpdesk');

            // Search for something that doesn't match either form name/description
            await getSearchInput(page).fill('nonexistent');

            // Pinned form should still appear, regular form should not
            await expect(page.getByRole('link', { name: pinned_form }))
                .toBeAttached()
            ;
            await expect(page.getByRole('link', { name: regular_form }))
                .not.toBeAttached()
            ;
        } finally {
            // Unpin the pinned form: a pinned form shows up at the top of the
            // service catalog of its entity.
            await profile.set(Profiles.SuperAdmin);
            await api.updateItem('Glpi\\Form\\Form', pinned_form_id, {
                'is_pinned': false,
            });
        }
    });

    test('can search both forms and FAQ items together', async ({
        page,
        profile,
        api,
    }) => {
        const unique_id = getUniqueId();
        const form_name = `Test Form ${unique_id}`;
        const faq_name = `Test FAQ ${unique_id}`;

        await profile.set(Profiles.SuperAdmin);

        // Create a form
        await createActiveForm(api, form_name, 0, 'Form for testing search');

        // Create a FAQ item
        await createKnowledgeBaseItem(api, faq_name, {
            'description': 'FAQ for testing search',
        });

        await profile.set(Profiles.SelfService);
        await page.goto('/Helpdesk');

        // Search should find both.
        await getSearchInput(page).fill(unique_id);

        await expect(page.getByRole('link', { name: form_name }))
            .toBeAttached()
        ;
        await expect(page.getByRole('link', { name: faq_name }))
            .toBeAttached()
        ;
    });
});
