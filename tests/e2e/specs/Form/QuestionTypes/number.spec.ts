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
import { expect, test } from '../../../fixtures/glpi_fixture';
import { FormPage } from '../../../pages/FormPage';
import { Api } from '../../../utils/Api';
import { Profiles } from '../../../utils/Profiles';
import { getWorkerEntityId } from '../../../utils/WorkerEntities';

test.describe('Number form question type', () => {
    const setupForm = async (form: FormPage, api: Api): Promise<void> => {
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: `Tests form for the number form question type suite - ${randomUUID()}`,
            entities_id: getWorkerEntityId(),
        });
        await form.goto(form_id);

        // Add a question
        const question = await form.addQuestion('Test number question');

        // Change question type
        await form.setQuestionType(question, 'Short answer');

        // Change question sub type
        await form.setSubQuestionType(question, 'Number');
    };

    const assertDefaultValue = async (
        form: FormPage,
        value: string
    ): Promise<void> => {
        // Define default value
        await form.getSpinButton('Default value').fill(value);

        // Save and reload the page
        await form.doSaveFormEditorAndReload();

        // Check the default value
        await expect(form.getSpinButton('Default value')).toHaveValue(value);

        // Go to preview page
        await form.doPreviewForm();

        // Check the default value in the preview page
        await expect(form.getSpinButton('Test number question'))
            .toHaveValue(value)
        ;

        // Submit
        await form.getButton('Submit').click();

        // Check the form was submitted
        await expect(form.getAlert('Item successfully created')).toBeVisible();
    };

    test('should be able to define an integer as default value', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);
        await assertDefaultValue(form, '42');
    });

    test('should be able to define a float as default value', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);
        await assertDefaultValue(form, '3.14');
    });
});
