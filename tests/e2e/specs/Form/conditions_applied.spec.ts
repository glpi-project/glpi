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
import { FormPreviewPage } from '../../pages/FormRenderPage';
import { Profiles } from '../../utils/Profiles';

test(`Conditions are applied on the submit button`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const info = await formImporter.importForm("submit-button-visible-if-equals.json");
    const preview = new FormPreviewPage(page);
    await preview.goto(info.getId());

    // The question is empty, the submit button should not be visible
    await expect(page.getByRole('button', { name: 'Submit' })).toBeHidden();

    // Set answer to "I love GLPI", the submit button should become visible
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('I love GLPI');
    await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();

    // Change answer to "I love GLPI 2", the submit button should be hidden again
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('I love GLPI 2');
    await expect(page.getByRole('button', { name: 'Submit' })).toBeHidden();
});

test(`Conditions are applied on questions`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const info = await formImporter.importForm("question-conditions-applied.json");
    const preview = new FormPreviewPage(page);
    await preview.goto(info.getId());

    // Default state: "always visible" is visible, "visible if" is hidden, "hidden if" is visible
    await expect(page.getByRole('heading', { name: 'My question that is always visible' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeHidden();
    await expect(page.getByRole('heading', { name: 'My question that is hidden if some criteria are met' })).toBeVisible();

    // Set answer to "Expected answer 1": "visible if" becomes visible
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 1');
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My question that is hidden if some criteria are met' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My question that is always visible' })).toBeVisible();

    // Set answer to "Expected answer 2": "hidden if" becomes hidden
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 2');
    await expect(page.getByRole('heading', { name: 'My question that is hidden if some criteria are met' })).toBeHidden();
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeHidden();
    await expect(page.getByRole('heading', { name: 'My question that is always visible' })).toBeVisible();
});

test(`Conditions are applied on questions that uses array values`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const info = await formImporter.importForm("question-array-conditions-applied.json");
    const preview = new FormPreviewPage(page);
    await preview.goto(info.getId());

    // Default state: target question is hidden (no checkboxes checked)
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeHidden();

    // Check Option 1 and Option 4: target question becomes visible
    await page.getByRole('checkbox', { name: 'Option 1' }).check();
    await page.getByRole('checkbox', { name: 'Option 4' }).check();
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeVisible();

    // Uncheck Option 1: target question becomes hidden again
    await page.getByRole('checkbox', { name: 'Option 1' }).uncheck();
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeHidden();
});

test(`Conditions using "Radio" question as subject`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const info = await formImporter.importForm("condition-subject-radio.json");
    const preview = new FormPreviewPage(page);
    await preview.goto(info.getId());

    // Default state: target question is hidden
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeHidden();

    // Select Option 3: target question becomes visible
    await page.getByRole('radio', { name: 'Option 3' }).check();
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeVisible();

    // Select Option 1: target question becomes hidden again
    await page.getByRole('radio', { name: 'Option 1' }).check();
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeHidden();
});

test(`Conditions using "Dropdown" question as subject`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const info = await formImporter.importForm("condition-subject-dropdown.json");
    const preview = new FormPreviewPage(page);
    await preview.goto(info.getId());

    // Default state: target question is hidden
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeHidden();

    // Select Option 3: target question becomes visible
    const dropdown = preview.getDropdownByLabel('My question used as a criteria');
    await preview.doSetDropdownValue(dropdown, 'Option 3');
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeVisible();

    // Select Option 1: target question becomes hidden again
    await preview.doSetDropdownValue(dropdown, 'Option 1');
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeHidden();
});

test(`Conditions are applied on comments`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const info = await formImporter.importForm("comment-conditions-applied.json");
    const preview = new FormPreviewPage(page);
    await preview.goto(info.getId());

    // Default state: always visible and hidden_if are visible, visible_if is hidden
    await expect(page.getByRole('heading', { name: 'My comment that is always visible' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My comment that is hidden if some criteria are met' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My comment that is visible if some criteria are met' })).toBeHidden();

    // Set answer to "Expected answer 1": visible_if comment becomes visible
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 1');
    await expect(page.getByRole('heading', { name: 'My comment that is visible if some criteria are met' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My comment that is hidden if some criteria are met' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My comment that is always visible' })).toBeVisible();

    // Set answer to "Expected answer 2": hidden_if comment becomes hidden
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 2');
    await expect(page.getByRole('heading', { name: 'My comment that is hidden if some criteria are met' })).toBeHidden();
    await expect(page.getByRole('heading', { name: 'My comment that is visible if some criteria are met' })).toBeHidden();
    await expect(page.getByRole('heading', { name: 'My comment that is always visible' })).toBeVisible();
});

test(`Conditions are applied on sections`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const info = await formImporter.importForm("section-conditions-applied.json");
    const preview = new FormPreviewPage(page);
    await preview.goto(info.getId());

    // Default state: First section, always visible, hidden_if visible (3 sections)
    await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
    await page.getByRole('button', { name: 'Continue' }).click();
    await expect(page.getByRole('heading', { name: 'My section that is always visible' })).toBeVisible();
    await page.getByRole('button', { name: 'Continue' }).click();
    await expect(page.getByRole('heading', { name: 'My section that is hidden if some criteria are met' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
    await page.getByRole('button', { name: 'Back' }).click();
    await page.getByRole('button', { name: 'Back' }).click();

    // Set answer to "Expected answer 1": visible_if section appears (4 sections)
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 1');
    await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
    await page.getByRole('button', { name: 'Continue' }).click();
    await expect(page.getByRole('heading', { name: 'My section that is always visible' })).toBeVisible();
    await page.getByRole('button', { name: 'Continue' }).click();
    await expect(page.getByRole('heading', { name: 'My section that is visible if some criteria are met' })).toBeVisible();
    await page.getByRole('button', { name: 'Continue' }).click();
    await expect(page.getByRole('heading', { name: 'My section that is hidden if some criteria are met' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
    await page.getByRole('button', { name: 'Back' }).click();
    await page.getByRole('button', { name: 'Back' }).click();
    await page.getByRole('button', { name: 'Back' }).click();

    // Set answer to "Expected answer 2": hidden_if and visible_if sections disappear (2 sections)
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 2');
    await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
    await page.getByRole('button', { name: 'Continue' }).click();
    await expect(page.getByRole('heading', { name: 'My section that is always visible' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
});

test(`Cascading visibility conditions on questions`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const info = await formImporter.importForm("cascading-question-visibility.json");
    const preview = new FormPreviewPage(page);
    await preview.goto(info.getId());

    // Default state: criteria visible, both conditional questions hidden
    await expect(page.getByRole('heading', { name: 'My question used as a criteria' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeHidden();
    await expect(page.getByRole('heading', { name: 'My question that is visible if previous question is visible' })).toBeHidden();

    // Set answer to "Expected answer 1": both conditional questions become visible (cascading)
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 1');
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My question that is visible if previous question is visible' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My question used as a criteria' })).toBeVisible();

    // Change answer: both conditional questions become hidden again
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 2');
    await expect(page.getByRole('heading', { name: 'My question that is visible if some criteria are met' })).toBeHidden();
    await expect(page.getByRole('heading', { name: 'My question that is visible if previous question is visible' })).toBeHidden();
    await expect(page.getByRole('heading', { name: 'My question used as a criteria' })).toBeVisible();
});

test(`Cascading visibility conditions on comments`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const info = await formImporter.importForm("cascading-comment-visibility.json");
    const preview = new FormPreviewPage(page);
    await preview.goto(info.getId());

    // Default state: criteria visible, both conditional comments hidden
    await expect(page.getByRole('heading', { name: 'My question used as a criteria' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My comment that is visible if some criteria are met' })).toBeHidden();
    await expect(page.getByRole('heading', { name: 'My comment that is visible if previous comment is visible' })).toBeHidden();

    // Set answer to "Expected answer 1": both conditional comments become visible (cascading)
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 1');
    await expect(page.getByRole('heading', { name: 'My question used as a criteria' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My comment that is visible if some criteria are met' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My comment that is visible if previous comment is visible' })).toBeVisible();

    // Change answer: both conditional comments become hidden again
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 2');
    await expect(page.getByRole('heading', { name: 'My question used as a criteria' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'My comment that is visible if some criteria are met' })).toBeHidden();
    await expect(page.getByRole('heading', { name: 'My comment that is visible if previous comment is visible' })).toBeHidden();
});

test(`Cascading visibility conditions on sections`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const info = await formImporter.importForm("cascading-section-visibility.json");
    const preview = new FormPreviewPage(page);
    await preview.goto(info.getId());

    // Default state: only first section visible (1 section)
    await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();

    // Set answer to "Expected answer 1": both conditional sections appear (3 sections)
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 1');
    await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
    await page.getByRole('button', { name: 'Continue' }).click();
    await expect(page.getByRole('heading', { name: 'My section that is visible if some criteria are met' })).toBeVisible();
    await page.getByRole('button', { name: 'Continue' }).click();
    await expect(page.getByRole('heading', { name: 'My section that is visible if previous section is visible' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
    await page.getByRole('button', { name: 'Back' }).click();
    await page.getByRole('button', { name: 'Back' }).click();

    // Change answer: back to only first section (1 section)
    await page.getByRole('textbox', { name: 'My question used as a criteria' }).fill('Expected answer 2');
    await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
});

test(`File upload updates visibility of a target question`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const info = await formImporter.importForm("file-upload-conditions.json");
    const preview = new FormPreviewPage(page);
    await preview.goto(info.getId());

    // Target question is hidden initially
    await expect(page.getByRole('heading', { name: 'My target question' })).toBeHidden();

    // Upload a file to the file question
    const file_region = page.getByRole('region', { name: 'My file question' });
    const file_upload_response = page.waitForResponse('**/ajax/fileupload.php');
    await preview.doAddFileToUploadArea('uploads/bar.png', file_region);
    await file_upload_response;

    // Target question becomes visible
    await expect(page.getByRole('heading', { name: 'My target question' })).toBeVisible();

    // Remove the uploaded file
    await file_region.getByTitle('Delete').click();

    // Target question becomes hidden again
    await expect(page.getByRole('heading', { name: 'My target question' })).toBeHidden();
});
