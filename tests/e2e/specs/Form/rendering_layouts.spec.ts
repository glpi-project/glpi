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

async function checkMandatoryQuestion(page: Page, name: string): Promise<void> {
    const region = page.getByRole('region', { name });
    await expect(region.getByRole('textbox')).toHaveAttribute('aria-invalid', 'true');
    await expect(region.getByTestId('validation-error-message').first()).toContainText('This field is mandatory');
}

async function checkMandatoryLongTextQuestion(page: Page, question_name: string): Promise<void> {
    const region = page.getByRole('region', { name: question_name });
    await expect(region.getByTestId('validation-error-message').first()).toContainText('This field is mandatory');
}

async function checkMandatoryCheckboxQuestion(page: Page, question_name: string): Promise<void> {
    const region = page.getByRole('region', { name: question_name });
    const checkboxes = region.getByRole('checkbox');
    const count = await checkboxes.count();
    for (let i = 0; i < count; i++) {
        await expect(checkboxes.nth(i)).toHaveAttribute('aria-invalid', 'true');
    }
    await expect(region.getByTestId('validation-error-message').first()).toContainText('This field is mandatory');
}

test.describe('Step-by-step layout (default)', () => {
    test('Shows navigation buttons and one section at a time with validation', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);

        const info = await formImporter.importForm('rendering_layouts/step-by-step-three-sections-mandatory.json');

        await page.goto(`/Form/Render/${info.getId()}`);

        // Should show only first section
        await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Second section' })).toHaveCount(0);
        await expect(page.getByRole('heading', { name: 'Third section' })).toHaveCount(0);

        // Should show Continue button but no Back button on first section
        await expect(page.getByRole('button', { name: 'Continue' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Back' })).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Submit' })).toHaveCount(0);

        // Try to continue without filling mandatory field
        await page.getByRole('button', { name: 'Continue' }).click();
        await checkMandatoryQuestion(page, 'First question');
        await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();

        // Fill first question and continue
        await page.getByRole('textbox', { name: 'First question' }).fill('Answer 1');
        await page.getByRole('button', { name: 'Continue' }).click();

        // Should now show second section only
        await expect(page.getByRole('heading', { name: 'Second section' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'First section' })).toHaveCount(0);
        await expect(page.getByRole('heading', { name: 'Third section' })).toHaveCount(0);

        // Should show both Back and Continue buttons
        await expect(page.getByRole('button', { name: 'Back' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Continue' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Submit' })).toHaveCount(0);

        // Test back navigation
        await page.getByRole('button', { name: 'Back' }).click();
        await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
        await expect(page.getByRole('textbox', { name: 'First question' })).toHaveValue('Answer 1');

        // Navigate forward again
        await page.getByRole('button', { name: 'Continue' }).click();
        await expect(page.getByRole('heading', { name: 'Second section' })).toBeVisible();

        // Try to continue without filling second mandatory field
        await page.getByRole('button', { name: 'Continue' }).click();
        await checkMandatoryQuestion(page, 'Second question');

        // Fill second question and continue
        await page.getByRole('textbox', { name: 'Second question' }).fill('Answer 2');
        await page.getByRole('button', { name: 'Continue' }).click();

        // Should now show third section (last one)
        await expect(page.getByRole('heading', { name: 'Third section' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Second section' })).toHaveCount(0);

        // Should show Back and Submit buttons (no Continue on last section)
        await expect(page.getByRole('button', { name: 'Back' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Continue' })).toHaveCount(0);

        // Can submit without filling optional field
        await page.getByRole('button', { name: 'Submit' }).click();
        await expect(page.getByText('Form submitted')).toBeVisible();
    });

    test('Works correctly with single section form', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);

        const info = await formImporter.importForm('rendering_layouts/step-by-step-single-section.json');

        await page.goto(`/Form/Render/${info.getId()}`);

        // If the form has only one section, it should not show the section title
        await expect(page.getByRole('heading', { name: 'First section' })).toHaveCount(0);

        // Should show only Submit button (no navigation needed)
        await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Continue' })).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Back' })).toHaveCount(0);

        // Should be able to submit
        await page.getByRole('textbox', { name: 'Only question' }).fill('Answer');
        await page.getByRole('button', { name: 'Submit' }).click();
        await expect(page.getByText('Form submitted')).toBeVisible();
    });
});

test.describe('Single page layout', () => {
    test('Shows all sections at once with only submit button', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);

        const info = await formImporter.importForm('rendering_layouts/single-page-three-sections-mandatory.json');

        await page.goto(`/Form/Render/${info.getId()}`);

        // Should show all sections at once
        await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Second section' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Third section' })).toBeVisible();

        // All questions should be visible
        await expect(page.getByRole('textbox', { name: 'First question' })).toBeVisible();
        await expect(page.getByRole('textbox', { name: 'Second question' })).toBeVisible();
        await expect(page.getByRole('textbox', { name: 'Third question' })).toBeVisible();

        // Should show only Submit button (no navigation buttons)
        await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Continue' })).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Back' })).toHaveCount(0);

        // Try to submit without filling mandatory fields
        await page.getByRole('button', { name: 'Submit' }).click();

        // Should show validation errors for both mandatory fields
        await checkMandatoryQuestion(page, 'First question');
        await checkMandatoryQuestion(page, 'Second question');

        // Fill only first mandatory field
        await page.getByRole('textbox', { name: 'First question' }).fill('Answer 1');
        await page.getByRole('button', { name: 'Submit' }).click();

        // Should still show error for second mandatory field
        await checkMandatoryQuestion(page, 'Second question');

        // Fill second mandatory field
        await page.getByRole('textbox', { name: 'Second question' }).fill('Answer 2');

        // Can submit without filling optional field
        await page.getByRole('button', { name: 'Submit' }).click();
        await expect(page.getByText('Form submitted')).toBeVisible();
    });

    test('Works correctly with single section form', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);

        const info = await formImporter.importForm('rendering_layouts/single-page-single-section.json');

        await page.goto(`/Form/Render/${info.getId()}`);

        // Should show the section but no section title (as it's the only one)
        await expect(page.getByRole('heading', { name: 'First section' })).toHaveCount(0);
        await expect(page.getByRole('textbox', { name: 'Only question' })).toBeVisible();

        // Should show only Submit button
        await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Continue' })).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Back' })).toHaveCount(0);

        // Should be able to submit
        await page.getByRole('textbox', { name: 'Only question' }).fill('Answer');
        await page.getByRole('button', { name: 'Submit' }).click();
        await expect(page.getByText('Form submitted')).toBeVisible();
    });

    test('Validates all sections at once with mixed field types', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);

        const info = await formImporter.importForm('rendering_layouts/single-page-mixed-fields.json');

        await page.goto(`/Form/Render/${info.getId()}`);

        // All questions should be visible
        await expect(page.getByRole('textbox', { name: 'Text field' })).toBeVisible();
        await expect(page.getByRole('region', { name: 'Long text field' })).toBeVisible();
        await expect(page.getByRole('textbox', { name: 'Number field' })).toBeVisible();
        await expect(page.getByRole('checkbox', { name: 'Option 1' })).toBeVisible();
        await expect(page.getByRole('checkbox', { name: 'Option 2' })).toBeVisible();
        await expect(page.getByRole('checkbox', { name: 'Option 3' })).toBeVisible();

        // Try to submit without filling any fields
        await page.getByRole('button', { name: 'Submit' }).click();

        // All should show validation errors
        await checkMandatoryQuestion(page, 'Text field');
        await checkMandatoryLongTextQuestion(page, 'Long text field');
        await checkMandatoryQuestion(page, 'Number field');
        await checkMandatoryCheckboxQuestion(page, 'Checkbox field');

        // Fill all fields
        await page.getByRole('textbox', { name: 'Text field' }).fill('Text answer');
        const long_text_section = page.getByRole('region', { name: 'Long text field' });
        // eslint-disable-next-line playwright/no-raw-locators
        const long_text_iframe = long_text_section.locator('iframe').first();
        await long_text_iframe.waitFor({ state: 'attached' });
        // eslint-disable-next-line playwright/no-raw-locators
        const long_text_body = long_text_iframe.contentFrame().locator('body');
        await long_text_body.click();
        await long_text_body.pressSequentially('Long text answer');
        await page.getByRole('textbox', { name: 'Number field' }).fill('123');
        await page.getByRole('checkbox', { name: 'Option 1' }).check();

        // Should be able to submit
        await page.getByRole('button', { name: 'Submit' }).click();
        await expect(page.getByText('Form submitted')).toBeVisible();
    });
});

test.describe('Layout consistency and edge cases', () => {
    test('Prevents multiple form submissions in both layouts', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);

        // Test step-by-step layout
        const step_info = await formImporter.importForm('rendering_layouts/step-by-step-one-question.json');

        await page.goto(`/Form/Render/${step_info.getId()}`);
        await page.getByRole('textbox', { name: 'Question' }).fill('Answer');
        await page.getByRole('button', { name: 'Submit' }).click();

        // Submit button should be disabled after first click
        await expect(page.getByRole('button', { name: 'Submit' })).toHaveClass(/pointer-events-none/);

        // Test single page layout
        const single_info = await formImporter.importForm('rendering_layouts/single-page-one-question.json');

        await page.goto(`/Form/Render/${single_info.getId()}`);
        await page.getByRole('textbox', { name: 'Question' }).fill('Answer');
        await page.getByRole('button', { name: 'Submit' }).click();

        // Submit button should be disabled after first click
        await expect(page.getByRole('button', { name: 'Submit' })).toHaveClass(/pointer-events-none/);
    });

    test('Preserves form data when navigating in step-by-step mode', async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);

        const info = await formImporter.importForm('rendering_layouts/step-by-step-two-sections.json');

        await page.goto(`/Form/Render/${info.getId()}`);

        // Fill first question
        await page.getByRole('textbox', { name: 'First question' }).fill('First answer');
        await page.getByRole('button', { name: 'Continue' }).click();

        // Fill second question
        await page.getByRole('textbox', { name: 'Second question' }).fill('Second answer');

        // Navigate back
        await page.getByRole('button', { name: 'Back' }).click();

        // First question should still have its value
        await expect(page.getByRole('textbox', { name: 'First question' })).toHaveValue('First answer');

        // Navigate forward again
        await page.getByRole('button', { name: 'Continue' }).click();

        // Second question should still have its value
        await expect(page.getByRole('textbox', { name: 'Second question' })).toHaveValue('Second answer');

        // Submit form
        await page.getByRole('button', { name: 'Submit' }).click();
        await expect(page.getByText('Form submitted')).toBeVisible();
    });
});
