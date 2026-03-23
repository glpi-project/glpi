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
import { getWorkerEntityId } from '../../../utils/WorkerEntities';

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
        await entity.switchToWithoutRecursion(entity_id);
        api.refreshSession();

        const info = await formImporter.importForm('question_types/item-editor-test.json');
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
});
