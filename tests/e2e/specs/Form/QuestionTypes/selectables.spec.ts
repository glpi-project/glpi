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
import { expect, test } from '../../../fixtures/glpi_fixture';
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from '../../../utils/WorkerEntities';
import { FormPage } from "../../../pages/FormPage";

test('Can reorder options of a dropdown question and keep the order after saving', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create a form and navigate to its editor
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    const question = await form.addQuestion('Dropdown question');
    await form.setQuestionType(question, 'Dropdown');

    // Add three options to the dropdown question
    for (let i = 1; i <= 3; i++) {
        await question.getByRole('textbox', { name: 'Selectable option' }).last().fill(`Option ${i}`);
    }

    await question.getByRole('button', { name: 'Move option', exact: true }).last().click();

    // Move last option to the first position
    await question.getByRole('button', { name: 'Move option', exact: true }).last()
        .dragTo(question.getByRole('button', { name: 'Move option', exact: true }).first());

    // Save and reload the form
    await form.doSaveFormEditor();
    await form.goto(form_id);

    // Focus on the question to load its options
    await form.getLastQuestion().click({ position: { x: 0, y: 0 } });

    // Assert that the order of the options is correct after reloading
    const options = question.getByRole('textbox', { name: 'Selectable option' });
    await expect(options.nth(0)).toHaveValue('Option 3');
    await expect(options.nth(1)).toHaveValue('Option 1');
    await expect(options.nth(2)).toHaveValue('Option 2');
    await expect(options.nth(3)).toHaveValue('');
});
