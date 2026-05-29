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
import { Profiles } from "../../utils/Profiles";
import { FormPage } from "../../pages/FormPage";

test.describe('Form tags', () => {
    test('tags autocompletion is loaded and values are preserved on reload', async ({ page, profile, api, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);

        const info = await formImporter.importForm('form-tags.json');
        const form_id = info.getId();

        const sections = await api.getSubItems(
            'Glpi\\Form\\Form', form_id, 'Glpi\\Form\\Section'
        );
        const questions = await api.getSubItems(
            'Glpi\\Form\\Section', sections[0].id, 'Glpi\\Form\\Question'
        );
        const last_name_question_id = questions.find(
            (q: {name: string}) => q.name === 'Last name'
        ).id;

        const form = new FormPage(page);
        await form.gotoDestinationTab(form_id);

        // Autocomplete not yet open
        await expect(page.getByRole('menuitem', { name: 'Form name: Test form for the form tags suite' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Section: Section 1' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Question: First name' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Question: Last name' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Answer: First name' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Answer: Last name' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Comment title: Comment title' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Comment description: Comment description' })).toHaveCount(0);

        const content_region = page.getByRole('region', { name: 'Content configuration' });
        const rich_text = form.getRichTextByLabel('Content', content_region);

        // Uncheck auto config
        await content_region.getByRole('checkbox', { name: 'Auto config' }).uncheck();

        // Clear and type # to trigger autocomplete
        await rich_text.click();
        await rich_text.press('Control+a');
        await rich_text.press('Backspace');
        await rich_text.pressSequentially('#');

        // All autocomplete items should be visible
        await expect(page.getByRole('menuitem', { name: 'Form name: Test form for the form tags suite' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Section: Section 1' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Question: First name' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Question: Last name' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Answer: First name' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Answer: Last name' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Comment title: Comment title' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Comment description: Comment description' })).toBeVisible();

        // Filter results
        await rich_text.pressSequentially('Last');
        await expect(page.getByRole('menuitem', { name: 'Form name: Test form for the form tags suite' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Question: First name' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Question: Last name' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Answer: First name' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Answer: Last name' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Comment title: Comment title' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Comment description: Comment description' })).toHaveCount(0);

        // Click to insert tag
        await page.getByRole('menuitem', { name: 'Question: Last name' }).click();

        // Autocomplete should close
        await expect(page.getByRole('menuitem', { name: 'Form name: Test form for the form tags suite' })).toHaveCount(0);
        await expect(page.getByRole('menuitem', { name: 'Question: Last name' })).toHaveCount(0);

        // Tag should be inserted with correct attributes
        const tag = rich_text.getByText('#Question: Last name');
        await expect(tag).toHaveAttribute('data-form-tag', 'true');
        await expect(tag).toHaveAttribute('data-form-tag-value', String(last_name_question_id));

        // Save
        await page.getByRole('button', { name: 'Update item' }).click();
        await expect(page.getByRole('alert').filter({ hasText: 'Item successfully updated' })).toBeVisible();

        // Verify persistence after save
        const content_region_after = page.getByRole('region', { name: 'Content configuration' });
        const rich_text_after = form.getRichTextByLabel('Content', content_region_after);
        const tag_after = rich_text_after.getByText('#Question: Last name');
        await expect(tag_after).toHaveAttribute('data-form-tag', 'true');
        await expect(tag_after).toHaveAttribute('data-form-tag-value', String(last_name_question_id));
    });
});
