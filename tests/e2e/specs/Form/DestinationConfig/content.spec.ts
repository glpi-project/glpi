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
import { Profiles } from "../../../utils/Profiles";
import { FormPage } from "../../../pages/FormPage";

test.describe('Content configuration', () => {
    let form_page: FormPage;
    let form_id: number;

    test.beforeEach(async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);
        const info = await formImporter.importForm('destination_config_fields/content-title-config.json');
        form_id = info.getId();
        await form_page.gotoDestinationTab(form_id);
    });

    test('Can create ticket using default configuration', async ({ page }) => {
        // Go to preview
        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();

        // Fill form
        await page.getByRole('textbox', { name: 'What is your name ?' }).fill('John doe');
        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My form name' }).click();

        // Check ticket values, description should contain answers
        await expect(page.getByText('1) What is your name ?')).toBeVisible();
        await expect(page.getByText(': John doe')).toBeVisible();
    });

    test('Formatted text alongside form tags is preserved after save', async ({ page }) => {
        const content_region = page.getByRole('region', { name: 'Content configuration' });
        const content_body = form_page.getRichTextByLabel('Content', content_region);

        // Disable auto-config to enable manual editing
        await content_region.getByRole('checkbox', { name: 'Auto config' }).uncheck();

        // Clear existing content and write underlined text followed by a form tag.
        // Underline produces a <span style="text-decoration: underline;"> in TinyMCE.
        // The regression being tested: refreshTagsContent used to match from the first
        // <span (the underline span) up to the closing </span> of the form-tag span,
        // swallowing the formatted text entirely.
        await content_body.click();
        await content_body.press('Control+a');
        await content_body.press('Backspace');
        await content_body.press('Control+u');
        await content_body.pressSequentially('My formatted text');
        await content_body.press('Control+u');
        await content_body.pressSequentially(' #');
        await page.getByRole('menuitem', { name: 'Answer: What is your name ?' }).click();

        // Save and reload the destination tab to run refreshTagsContent server-side
        await form_page.doSaveDestination();
        await form_page.gotoDestinationTab(form_id);

        // Both the formatted text and the tag must survive the reload
        const content_region_after = page.getByRole('region', { name: 'Content configuration' });
        const content_body_after = form_page.getRichTextByLabel('Content', content_region_after);
        await expect(content_body_after).toContainText('My formatted text');
        await expect(
            content_body_after.getByText('#Answer: What is your name ?')
        ).toHaveAttribute('data-form-tag', 'true');
    });
});
