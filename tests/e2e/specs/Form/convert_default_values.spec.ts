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
import { test, expect } from '../../fixtures/glpi_fixture';
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { FormPage } from "../../pages/FormPage";

test('Convert default value between short text and email types', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test convert text/email - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);
    const question = await form.addQuestion('Test question');

    const sub_type = form.getDropdownByLabel('Question sub type', question);
    await expect(sub_type).toContainText('Text');

    await question.getByRole('textbox', { name: 'Default value' }).fill('Default value for short text');

    await form.doSetDropdownValue(sub_type, 'Emails', false);

    await expect(
        question.getByRole('textbox', { name: 'Default value' })
    ).toHaveValue('Default value for short text');
});

test('Convert default value between email and short text types', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test convert email/text - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);
    const question = await form.addQuestion('Test question');

    const sub_type = form.getDropdownByLabel('Question sub type', question);
    await form.doSetDropdownValue(sub_type, 'Emails', false);

    await question.getByRole('textbox', { name: 'Default value' }).fill('Default value for short text');

    await form.doSetDropdownValue(sub_type, 'Text', false);

    await expect(
        question.getByRole('textbox', { name: 'Default value' })
    ).toHaveValue('Default value for short text');
});

test('Convert default value between short text and long text types', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test convert short/long text - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);
    const question = await form.addQuestion('Test question');

    const sub_type = form.getDropdownByLabel('Question sub type', question);
    await expect(sub_type).toContainText('Text');

    await question.getByRole('textbox', { name: 'Default value' }).fill('Default value for short text');

    await form.doChangeQuestionType(question, 'Long answer');

    const default_value = await form.initRichTextByLabel('Default value', form.getRegion('Question details'));
    await expect(default_value).toHaveText('Default value for short text');
});

test('Convert default value between long text and short text types', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test convert long/short text - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);
    const question = await form.addQuestion('Test question');

    await form.doChangeQuestionType(question, 'Long answer');

    const rich_text = await form.initRichTextByLabel('Default value', form.getRegion('Question details'));
    await rich_text.fill('This is a much longer default value for short text. It contains multiple lines and line breaks.\nLine 1\nLine 2\nLine 3');

    await form.doChangeQuestionType(question, 'Short answer');

    const sub_type = form.getDropdownByLabel('Question sub type', question);
    await expect(sub_type).toContainText('Text');

    await expect(
        question.getByRole('textbox', { name: 'Default value' })
    ).toHaveValue('This is a much longer default value for short text. It contains multiple lines and line breaks.Line 1Line 2Line 3');
});

test('Convert default value between radio and checkbox types', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test convert radio/checkbox - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);
    const question = await form.addQuestion('Test question');

    await form.setQuestionType(question, 'Radio');

    await question.getByRole('textbox', { name: 'Selectable option' }).nth(0).fill('Option 1');
    await question.getByRole('textbox', { name: 'Selectable option' }).nth(1).fill('Option 2');
    await question.getByRole('textbox', { name: 'Selectable option' }).nth(2).fill('Option 3');

    await question.getByRole('radio', { name: 'Default option' }).nth(1).check();

    await form.setQuestionType(question, 'Checkbox');

    await expect(question.getByRole('checkbox', { name: 'Default option' }).nth(1)).toBeChecked();
    await expect(question.getByRole('checkbox', { name: 'Default option' })).toHaveCount(4);
    await expect(question.getByRole('checkbox', { name: 'Default option' }).nth(3)).toBeDisabled();

    await form.setQuestionType(question, 'Radio');

    await expect(question.getByRole('radio', { name: 'Default option' }).nth(1)).toBeChecked();
    await expect(question.getByRole('radio', { name: 'Default option' })).toHaveCount(4);
    await expect(question.getByRole('radio', { name: 'Default option' }).nth(3)).toBeDisabled();

    await form.doSaveFormEditorAndReload();

    const question_after = form.getRegion('Question details');
    await question_after.getByRole('textbox', { name: 'Question name' }).click();
    await expect(question_after.getByRole('radio', { name: 'Default option' }).nth(1)).toBeChecked();
    await expect(question_after.getByRole('radio', { name: 'Default option' })).toHaveCount(4);
    await expect(question_after.getByRole('radio', { name: 'Default option' }).nth(0)).toBeEnabled();
    await expect(question_after.getByRole('radio', { name: 'Default option' }).nth(3)).toBeDisabled();
    await expect(question_after.getByRole('textbox', { name: 'Selectable option' }).nth(0)).not.toHaveValue('');
});

test('Convert default value between checkbox and dropdown type', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test convert checkbox/dropdown - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);
    const question = await form.addQuestion('Test question');

    await form.setQuestionType(question, 'Checkbox');

    await question.getByRole('textbox', { name: 'Selectable option' }).nth(0).fill('Option 1');
    await question.getByRole('textbox', { name: 'Selectable option' }).nth(1).fill('Option 2');
    await question.getByRole('textbox', { name: 'Selectable option' }).nth(2).fill('Option 3');

    await question.getByRole('checkbox', { name: 'Default option' }).nth(1).check();

    await form.setQuestionType(question, 'Dropdown');

    await expect(form.getDropdownByLabel('Default option', question)).toContainText('Option 2');

    await form.setQuestionType(question, 'Checkbox');

    await expect(question.getByRole('checkbox', { name: 'Default option' }).nth(1)).toBeChecked();
    await expect(question.getByRole('checkbox', { name: 'Default option' })).toHaveCount(4);
    await expect(question.getByRole('checkbox', { name: 'Default option' }).nth(3)).toBeDisabled();

    await form.doSaveFormEditorAndReload();

    const question_after = form.getRegion('Question details');
    await question_after.getByRole('textbox', { name: 'Question name' }).click();
    await expect(question_after.getByRole('checkbox', { name: 'Default option' }).nth(1)).toBeChecked();
    await expect(question_after.getByRole('checkbox', { name: 'Default option' })).toHaveCount(4);
    await expect(question_after.getByRole('checkbox', { name: 'Default option' }).nth(0)).toBeEnabled();
    await expect(question_after.getByRole('checkbox', { name: 'Default option' }).nth(3)).toBeDisabled();
    await expect(question_after.getByRole('textbox', { name: 'Selectable option' }).nth(0)).not.toHaveValue('');
});

test('Convert default value between dropdown and radio type', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test convert dropdown/radio - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);
    const question = await form.addQuestion('Test question');

    await form.setQuestionType(question, 'Dropdown');

    await question.getByRole('textbox', { name: 'Selectable option' }).nth(0).fill('Option 1');
    await question.getByRole('textbox', { name: 'Selectable option' }).nth(1).fill('Option 2');
    await question.getByRole('textbox', { name: 'Selectable option' }).nth(2).fill('Option 3');

    await form.doSetDropdownValue(
        form.getDropdownByLabel('Default option', question),
        'Option 2'
    );

    await form.setQuestionType(question, 'Radio');

    await expect(question.getByRole('radio', { name: 'Default option' }).nth(1)).toBeChecked();
    await expect(question.getByRole('radio', { name: 'Default option' })).toHaveCount(4);
    await expect(question.getByRole('radio', { name: 'Default option' }).nth(3)).toBeDisabled();

    await form.setQuestionType(question, 'Dropdown');

    await expect(form.getDropdownByLabel('Default option', question)).toContainText('Option 2');

    await form.doSaveFormEditorAndReload();

    const question_after = form.getRegion('Question details');
    await question_after.getByRole('textbox', { name: 'Question name' }).click();
    await expect(form.getDropdownByLabel('Default option', question_after)).toContainText('Option 2');
});

test('Convert default value between long text (tinymce not init) and short text types', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test convert long text uninit/short - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);
    const question = await form.addQuestion('Long answer question');

    await form.doChangeQuestionType(question, 'Long answer');

    const rich_text = await form.initRichTextByLabel('Default value', form.getRegion('Question details'));
    await rich_text.fill('Default value for long text question');

    await form.doSaveFormEditorAndReload();

    const question_after = form.getRegion('Question details');
    await question_after.getByRole('textbox', { name: 'Question name' }).click();
    await form.doChangeQuestionType(question_after, 'Short answer');

    const sub_type = form.getDropdownByLabel('Question sub type', question_after);
    await expect(sub_type).toContainText('Text');
    await expect(
        question_after.getByRole('textbox', { name: 'Default value' })
    ).toHaveValue('Default value for long text question');
});
