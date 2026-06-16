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
import { FormPage } from '../../../pages/FormPage';
import { Profiles } from '../../../utils/Profiles';
import { getWorkerEntityId, getWorkerUserId } from '../../../utils/WorkerEntities';

test.describe('Item form question type', () => {
    let form: FormPage;
    let form_id: number;
    let entity_id: number;

    test.beforeEach(async ({ page, profile, entity, api, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form = new FormPage(page);

        // Create new entity
        entity_id = await api.createItem('Entity', {
            name: `Entity ${Date.now()}`,
            entities_id: getWorkerEntityId(),
        });

        // Switch to the new entity and refresh session to ensure the new entity is taken into account in the session
        await entity.switchToWithRecursion(entity_id);
        api.refreshSession();

        const info = await formImporter.importForm('question_types/item-editor-test.json', entity_id);
        form_id = info.getId();
        await form.goto(form_id);

    });

    test.afterEach(async ({ entity, api }) => {
        // Reset entity to default one to avoid issues with other tests in the same worker
        await entity.resetToDefaultWorkerEntity();
        api.refreshSession();
    });

    test('Adding new item option and compare select option labels', async ({ api }) => {
        // Add two computers
        await api.createItem('Computer', {
            name: 'Computer 1',
            entities_id: entity_id
        });
        await api.createItem('Computer', {
            name: 'Computer 2',
            entities_id: entity_id,
            serial: '123456',
            otherserial: '654321'
        });

        const persisted_question = form.getLastQuestion();
        await persisted_question.click({ position: { x: 0, y: 0 } });

        await form.getDropdownByLabel('Select an item', persisted_question).click();
        await expect(form.page.getByRole('option', { name: 'Computer 1' })).toBeVisible();
        await expect(form.page.getByRole('option', { name: 'Computer 2 - 123456 - 654321' })).toBeVisible();

        const new_question = await form.addQuestion('Item question');
        await form.setQuestionType(new_question, 'Item');
        await form.setSubQuestionType(new_question, 'GLPI Objects');
        await form.setItemTypeForItemQuestion(new_question, 'Computers');

        await form.getDropdownByLabel('Select an item', new_question).click();
        await expect(form.page.getByRole('option', { name: 'Computer 1' })).toBeVisible();
        await expect(form.page.getByRole('option', { name: 'Computer 2 - 123456 - 654321' })).toBeVisible();
    });

    test('Defining a new ticket as default value', async ({ api }) => {
        await api.createItem('Ticket', {
            name: 'Test ticket',
            content: '',
            entities_id: entity_id,
        });

        const question = form.getLastQuestion();
        await question.click({ position: { x: 0, y: 0 } });

        await form.doSetDropdownValue(
            form.getDropdownByLabel('Select an itemtype', question)
                .filter({ visible: true }),
            'Tickets'
        );

        await form.doSetDropdownValue(
            form.getDropdownByLabel('Select an item', question)
                .filter({ visible: true }),
            'Test ticket',
            false
        );

        // Save and check the default value is set in the preview
        await form.doSaveFormEditor();
        await form.doPreviewForm();
        await expect(form.getDropdownByLabel('Test item question')).toHaveText('Test ticket');
    });

    test('Defining multiple tickets as default value', async ({ api }) => {
        const ticket1_id = await api.createItem('Ticket', {
            name: 'Test ticket 1',
            content: '',
            entities_id: entity_id,
        });
        const ticket2_id = await api.createItem('Ticket', {
            name: 'Test ticket 2',
            content: '',
            entities_id: entity_id,
        });

        const question = form.getLastQuestion();
        await question.click({ position: { x: 0, y: 0 } });
        
        await form.doSetDropdownValue(
            form.getDropdownByLabel('Select an itemtype', question)
                .filter({ visible: true }),
            'Tickets'
        );
        
        await form.doEnableMultipleDropdownMode(question);

        await form.doSetDropdownValue(
            form.getDropdownByLabel('Select an item', question)
                .filter({ visible: true }),
            `Test ticket 1 - ${ticket1_id}`,
        );
        await form.doSetDropdownValue(
            form.getDropdownByLabel('Select an item', question)
                .filter({ visible: true }),
            `Test ticket 2 - ${ticket2_id}`,
        );

        // Save and check the default value is set in the preview
        await form.doSaveFormEditor();
        await form.doPreviewForm();
        await expect(form.getDropdownByLabel('Test item question')).toHaveText('×Test ticket 1×Test ticket 2');
    });

    test('Defining a new ITIL category as default value', async ({ api }) => {
        await api.createItem('ITILCategory', {
            name: 'Test ITIL category',
            entities_id: entity_id,
        });

        const question = form.getLastQuestion();
        await question.click({ position: { x: 0, y: 0 } });

        await form.setSubQuestionType(question, 'Dropdowns');
        await form.doSetDropdownValue(
            form.getDropdownByLabel('Select a dropdown type', question)
                .filter({ visible: true }),
            'ITIL categories'
        );

        // Select the new ITIL category as default value.
        // Tree dropdowns may display the same value multiple times (with a
        // "»" depth prefix), so the first matching option is used.
        const item_dropdown = form
            .getDropdownByLabel('Select a dropdown item', question)
            .filter({ visible: true });
        await item_dropdown.click();
        await form.page
            .getByRole('listbox')
            .getByRole('option', { name: 'Test ITIL category' })
            .first()
            .click();
        await expect(item_dropdown).toContainText('Test ITIL category');

        // Save and check the default value is set in the preview
        await form.doSaveFormEditor();
        await form.doPreviewForm();
        await expect(
            form.page.getByRole('combobox', { name: 'Test ITIL category' })
        ).toBeVisible();
    });

    test.describe('Default value with root entity', () => {
        let profile_user_id: number;

        test.beforeEach(async ({ api, profile, entity }) => {
            // We need to be in the root entity to be able to select it as
            // default value, so we grant the worker user access to it
            // (reverted in the afterEach hook)
            profile_user_id = await api.createItem('Profile_User', {
                users_id: getWorkerUserId(),
                profiles_id: Profiles.SuperAdmin,
                entities_id: 0,
                is_recursive: 1,
            });

            // Reload the profile to take the new entity access into account
            await profile.invalidateCachedProfile();
            await profile.set(Profiles.SuperAdmin);
            await entity.switchToWithRecursion(0);

            await form.goto(form_id);
        });

        test.afterEach(async ({ api, profile }) => {
            // Revert the root entity access
            await api.purgeItem('Profile_User', profile_user_id);
            await profile.invalidateCachedProfile();
            await profile.set(Profiles.SuperAdmin);
        });

        test('Can define the root entity as default value', async () => {
            const question = form.getLastQuestion();
            await question.click({ position: { x: 0, y: 0 } });

            await form.setItemTypeForItemQuestion(question, 'Entities');

            // Select the root entity as default value
            await form.doSetDropdownValue(
                form.getDropdownByLabel('Select an item', question)
                    .filter({ visible: true }),
                'Root entity'
            );

            // Check the default value is still set after reloading the editor
            await form.doSaveFormEditorAndReload();
            const reloaded_question = form.getLastQuestion();
            await reloaded_question.click({ position: { x: 0, y: 0 } });
            await expect(
                form.getDropdownByLabel('Select an item', reloaded_question)
                    .filter({ visible: true })
            ).toContainText('Root entity');

            // Check the default value is set in the preview
            await form.doPreviewForm();
            await expect(
                form.page.getByRole('combobox', { name: 'Root entity' })
            ).toBeVisible();
        });
    });
});
