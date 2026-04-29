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

import { test, expect } from '../../fixtures/glpi_fixture';
import { Page } from "@playwright/test";
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { FormPage } from "../../pages/FormPage";
import { GlpiPage } from "../../pages/GlpiPage";

async function expectMandatoryQuestion(page: Page, name: string): Promise<void> {
    const textbox = page.getByRole('textbox', { name });
    await expect(textbox).toHaveAttribute('aria-invalid', 'true');
    const region = page.getByRole('region', { name });
    await expect(region.getByTestId('validation-error-message').first()).toContainText('This field is mandatory');
}

test.describe('Form rendering', () => {
    test('Can preset form fields using GET parameters', async ({ page, profile, api, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);

        const info = await formImporter.importForm('preset-form-fields.json');
        const form_id = info.getId();

        const sections = await api.getSubItems(
            'Glpi\\Form\\Form', form_id, 'Glpi\\Form\\Section'
        );
        const questions = await api.getSubItems(
            'Glpi\\Form\\Section', sections[0].id, 'Glpi\\Form\\Question'
        );
        const question_by_name = (name: string) => questions.find((q: {name: string}) => q.name === name);

        const params = new URLSearchParams({
            [question_by_name('Name').uuid]: 'My name',
            [question_by_name('Email').uuid]: 'myemail@teclib.com',
            [question_by_name('Age').uuid]: '29',
            [question_by_name('Urgency').uuid]: 'very loW',
            [question_by_name('Request type').uuid]: 'reQuest',
            [question_by_name('Prefered software').uuid]: 'I really like GLPI',
        });
        await page.goto(`/Form/Render/${form_id}?${params}`);

        const glpi = new GlpiPage(page);
        await expect(page.getByRole('textbox', { name: 'Name' })).toHaveValue('My name');
        await expect(page.getByRole('textbox', { name: 'Email' })).toHaveValue('myemail@teclib.com');
        await expect(page.getByRole('spinbutton', { name: 'Age' })).toHaveValue('29');

        await expect(glpi.getRichTextByLabel('Prefered software')).toHaveText('I really like GLPI');

        await expect(glpi.getDropdownByLabel('Urgency')).toContainText('Very low');
        await expect(glpi.getDropdownByLabel('Request type')).toContainText('Request');
    });

    test('Mandatory questions must be filled', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);

        const info = await formImporter.importForm('mandatory-questions-two-sections.json');
        await page.goto(`/Form/Render/${info.getId()}`);

        // Try to continue without answering mandatory question
        await page.getByRole('button', { name: 'Continue' }).click();
        await expectMandatoryQuestion(page, 'First question');
        await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Second section' })).toHaveCount(0);

        // Fill first question and continue
        await page.getByRole('textbox', { name: 'First question' }).fill('test');
        await page.getByRole('button', { name: 'Continue' }).click();
        await expect(page.getByRole('heading', { name: 'Second section' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'First section' })).toHaveCount(0);

        // Try to submit without answering second mandatory question
        await page.getByRole('button', { name: 'Submit' }).click();
        await expectMandatoryQuestion(page, 'Second question');
        await expect(page.getByRole('heading', { name: 'Second section' })).toBeVisible();
        await expect(page.getByText('Form submitted')).toBeHidden();

        // Fill second question and submit
        await page.getByRole('textbox', { name: 'Second question' }).fill('test');
        await page.getByRole('button', { name: 'Submit' }).click();
        await expect(page.getByRole('heading', { name: 'Second section' })).toHaveCount(0);
        await expect(page.getByText('Form submitted')).toBeVisible();
    });

    test('Mandatory question alert is correctly removed when navigating back', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);

        const info = await formImporter.importForm('mandatory-questions-two-sections.json');
        await page.goto(`/Form/Render/${info.getId()}`);

        // Trigger mandatory error
        await page.getByRole('button', { name: 'Continue' }).click();
        await expectMandatoryQuestion(page, 'First question');

        // Fill and continue
        await page.getByRole('textbox', { name: 'First question' }).fill('test');
        await page.getByRole('button', { name: 'Continue' }).click();
        await expect(page.getByRole('heading', { name: 'Second section' })).toBeVisible();

        // Go back
        await page.getByRole('button', { name: 'Back' }).click();
        await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();

        // Error message should be gone
        const textbox = page.getByRole('textbox', { name: 'First question' });
        await expect(textbox).not.toHaveAttribute('aria-invalid');
        await expect(textbox).not.toHaveAttribute('aria-errormessage');
        const region = page.getByRole('region', { name: 'First question' });
        await expect(region.getByTestId('validation-error-message')).toHaveCount(0);
    });

    test('Long text question completion works with multiple sections', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi = new GlpiPage(page);

        const info = await formImporter.importForm('long-text-two-sections.json');
        await page.goto(`/Form/Render/${info.getId()}`);

        // Try to continue without filling mandatory long text
        await page.getByRole('button', { name: 'Continue' }).click();

        // Fill the TinyMCE field
        const body = glpi.getRichTextByLabel('Description');
        await body.click();
        await body.pressSequentially('This is a test note');

        // Continue to second section
        await page.getByRole('button', { name: 'Continue' }).click();

        // First section hidden, second visible
        await expect(page.getByRole('region', { name: 'Description' })).toHaveCount(0);
        await expect(page.getByRole('textbox', { name: 'Short text' })).toBeVisible();
    });

    test('Displays untitled question and comment in preview', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: 'Test untitled items',
            entities_id: getWorkerEntityId(),
        });
        await form.goto(form_id);

        await page.getByRole('button', { name: 'Add a question' }).click();
        await page.getByRole('button', { name: 'Add a comment' }).click();
        await form.doSaveFormEditor();
        await form.doPreviewForm();

        await expect(page.getByRole('heading', { name: 'Untitled question' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Untitled comment' })).toBeVisible();
    });

    test('Items hidden by condition are ignored by destinations', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi = new GlpiPage(page);

        const info = await formImporter.importForm('form-with-hidden-items.json');
        await page.goto(`/Form/Render/${info.getId()}`);

        await glpi.doSetDropdownValue(
            glpi.getDropdownByLabel('Visible question'), 'Very high', false
        );
        await page.getByRole('button', { name: 'Submit' }).click();
        await page.getByRole('link', { name: 'Form with hidden items' }).click();

        await expect(page.getByTestId('form-field-urgency')).toContainText('Very high');
        await expect(page.getByText('Visible section')).toBeVisible();
        await expect(page.getByText('1) Visible question')).toBeVisible();
        await expect(page.getByText('2) Hidden question')).toHaveCount(0);
        await expect(page.getByText('Hidden section')).toHaveCount(0);
        await expect(page.getByText('1) Visible question inside hidden section')).toHaveCount(0);
    });

    test('Entity item question advanced configuration', async ({ page, profile, api, entity }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi = new GlpiPage(page);
        const uid = Date.now();

        const entity_root_id = await api.createItem('Entity', {
            name: `Test entity root ${uid}`,
            is_recursive: true,
            entities_id: getWorkerEntityId(),
        });
        api.refreshSession();
        const entity_child_id = await api.createItem('Entity', {
            name: `Test entity child ${uid}`,
            entities_id: entity_root_id,
        });
        api.refreshSession();
        await api.createItem('Entity', {
            name: `Test entity grandchild ${uid}`,
            entities_id: entity_child_id,
        });

        // Refresh the browser session's entity access to include newly created entities
        await entity.switchToWithRecursion(getWorkerEntityId());
        // Force a page load to ensure session is updated
        await page.goto('/front/central.php');
        await page.waitForLoadState('domcontentloaded');

        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: 'Test form with item question',
            entities_id: getWorkerEntityId(),
        });
        const sections = await api.getSubItems(
            'Glpi\\Form\\Form', form_id, 'Glpi\\Form\\Section'
        );
        const section_id = sections[0].id;

        const question_id = await api.createItem('Glpi\\Form\\Question', {
            forms_sections_id: section_id,
            name: 'Item question with advanced configuration',
            type: 'Glpi\\Form\\QuestionType\\QuestionTypeItem',
            vertical_rank: 0,
            extra_data: JSON.stringify({
                root_items_id: entity_root_id,
                subtree_depth: 0,
                selectable_tree_root: false,
                itemtype: "Entity",
            }),
        });

        await page.goto(`/Form/Render/${form_id}`);
        const dropdown = glpi.getDropdownByLabel('Item question with advanced configuration');
        await dropdown.click();
        await expect(page.getByRole('option', { name: '-----' })).toBeVisible();
        await expect(page.getByRole('option', { name: '-----' })).not.toHaveAttribute('aria-disabled', 'true');
        await expect(page.getByRole('option', { name: `»Test entity root ${uid}` })).toHaveAttribute('aria-disabled', 'true');
        await expect(page.getByRole('option', { name: `»Test entity child ${uid}` })).not.toHaveAttribute('aria-disabled');
        await expect(page.getByRole('option', { name: `»Test entity grandchild ${uid}` })).not.toHaveAttribute('aria-disabled');

        // Update to depth 1 with selectable root
        await api.updateItem('Glpi\\Form\\Question', question_id, {
            extra_data: JSON.stringify({
                root_items_id: entity_root_id,
                subtree_depth: "1",
                selectable_tree_root: "1",
                itemtype: "Entity",
            }),
        });
        await page.reload();

        const dropdown_after = glpi.getDropdownByLabel('Item question with advanced configuration');
        await dropdown_after.click();
        await expect(page.getByRole('option', { name: '-----' })).not.toHaveAttribute('aria-disabled', 'true');
        await expect(page.getByRole('option', { name: `»Test entity root ${uid}` })).not.toHaveAttribute('aria-disabled');
        await expect(page.getByRole('option', { name: `»Test entity child ${uid}` })).not.toHaveAttribute('aria-disabled');
        await expect(page.getByRole('option', { name: `»Test entity grandchild ${uid}` })).toHaveCount(0);
    });

    test('Location item dropdown advanced configuration', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi = new GlpiPage(page);
        const uid = Date.now();

        const location_root_id = await api.createItem('Location', {
            name: `Test location root ${uid}`,
            is_recursive: true,
            entities_id: getWorkerEntityId(),
        });
        const location_child_id = await api.createItem('Location', {
            name: `Test location child ${uid}`,
            locations_id: location_root_id,
            entities_id: getWorkerEntityId(),
        });
        await api.createItem('Location', {
            name: `Test location grandchild ${uid}`,
            locations_id: location_child_id,
            entities_id: getWorkerEntityId(),
        });

        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: 'Test form with item dropdown question',
            entities_id: getWorkerEntityId(),
        });
        const sections = await api.getSubItems(
            'Glpi\\Form\\Form', form_id, 'Glpi\\Form\\Section'
        );
        const section_id = sections[0].id;

        const question_id = await api.createItem('Glpi\\Form\\Question', {
            forms_sections_id: section_id,
            name: 'Item dropdown question with advanced configuration',
            type: 'Glpi\\Form\\QuestionType\\QuestionTypeItemDropdown',
            vertical_rank: 0,
            extra_data: JSON.stringify({
                categories_filter: [],
                root_items_id: location_root_id,
                subtree_depth: "0",
                selectable_tree_root: "0",
                itemtype: "Location",
            }),
        });

        await page.goto(`/Form/Render/${form_id}`);
        const dropdown = glpi.getDropdownByLabel('Item dropdown question with advanced configuration');
        await dropdown.click();
        await expect(page.getByRole('option', { name: '-----' })).not.toHaveAttribute('aria-disabled', 'true');
        await expect(page.getByRole('option', { name: `»Test location root ${uid}` })).toHaveAttribute('aria-disabled', 'true');
        await expect(page.getByRole('option', { name: `»Test location child ${uid}` })).not.toHaveAttribute('aria-disabled');
        await expect(page.getByRole('option', { name: `»Test location grandchild ${uid}` })).not.toHaveAttribute('aria-disabled');

        // Update to depth 1 with selectable root
        await api.updateItem('Glpi\\Form\\Question', question_id, {
            extra_data: JSON.stringify({
                categories_filter: [],
                root_items_id: location_root_id,
                subtree_depth: "1",
                selectable_tree_root: "1",
                itemtype: "Location",
            }),
        });
        await page.reload();

        const dropdown_after = glpi.getDropdownByLabel('Item dropdown question with advanced configuration');
        await dropdown_after.click();
        await expect(page.getByRole('option', { name: '-----' })).not.toHaveAttribute('aria-disabled', 'true');
        await expect(page.getByRole('option', { name: `»Test location root ${uid}` })).not.toHaveAttribute('aria-disabled');
        await expect(page.getByRole('option', { name: `»Test location child ${uid}` })).not.toHaveAttribute('aria-disabled');
        await expect(page.getByRole('option', { name: `»Test location grandchild ${uid}` })).toHaveCount(0);
    });

    test('Submit button appears when rich text content is filled', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi = new GlpiPage(page);

        const info = await formImporter.importForm('form_with_condition_on_richtext.json');
        await page.goto(`/Form/Render/${info.getId()}`);

        await expect(page.getByRole('button', { name: 'Submit' })).toBeHidden();

        // Fill the TinyMCE description
        const desc_body = glpi.getRichTextByLabel('Description');
        await desc_body.click();
        await desc_body.pressSequentially('Some content');

        await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
    });
});
