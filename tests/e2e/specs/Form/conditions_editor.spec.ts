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
import { FormPage } from '../../pages/FormPage';
import { Profiles } from '../../utils/Profiles';

test(`Set conditional visibility of the submit button`, async ({
    page,
    profile,
    formImporter,
}) => {
    // Go to a basic form with a text question
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("active-form.json");
    await form.goto(info.getId());

    // Add a condition on the submit button
    await form.doOpenSubmitButtonConditions();
    await form.doSetVisibilityStrategy("Visible if");
    await form.doFillStringCondition(
        0,
        "AND",
        "What is your name?",
        "Is equal to",
        "GLPI"
    );

    // Reload the page and validate that the condition is saved
    await form.doSaveFormEditorAndReload();
    await form.doOpenSubmitButtonConditions();
    const condition = form.getVisibleConditions();
    await expect(form.getConditionTarget(condition)).toHaveText("Questions - What is your name?");
    await expect(form.getConditionValueOperator(condition)).toHaveText("Is equal to");
    await expect(form.getTextConditionValue(condition)).toHaveValue("GLPI");
});

test(`Dynamic label for submit button visiblity`, async ({
    page,
    profile,
    formImporter,
}) => {
    // Go to a basic form with a text question
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("active-form.json");
    await form.goto(info.getId());

    // By default, the button should show "Always visible"
    await form.doOpenSubmitButtonConditions();
    await expect(form.getConfigureVisiblityButtonDisplayedValue()).toHaveText('Always visible 0');

    // Switch to "Visible if"
    await form.doSetVisibilityStrategy("Visible if...");
    await expect(form.getConfigureVisiblityButtonDisplayedValue()).toHaveText('Visible if... 0');

    // Switch to "Hidden if"
    await form.doSetVisibilityStrategy("Hidden if...");
    await expect(form.getConfigureVisiblityButtonDisplayedValue()).toHaveText('Hidden if... 0');

    // Switch to "Always visible if"
    await form.doSetVisibilityStrategy("Always visible");
    await expect(form.getConfigureVisiblityButtonDisplayedValue()).toHaveText('Always visible 0');
});

test(`Condition editor visiblity`, async ({
    page,
    profile,
    formImporter,
}) => {
    // Go to a basic form with a text question
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("active-form.json");
    await form.goto(info.getId());

    const question = form.getNthQuestion(0);

    // By default, the conditions dropdown is hidden
    await expect(question.getByTestId('visibility-dropdown')).toBeHidden();

    // Open the dropdown
    const dropdown = form.getVisiblityConditionDropdown(question);
    await form.doInitVisibilityConditionsDropdown(0);

    await expect(dropdown).toBeVisible();
    await expect(dropdown.getByTestId('conditions-editor')).toBeHidden();

    // Select "visible if" to trigger the editor
    await form.doSetVisibilityStrategy("Visible if...");
    await expect(dropdown).toBeVisible();
    await expect(dropdown.getByTestId('conditions-editor')).toBeVisible();

    // Switch back to "Always visible", editor should go away
    await form.doSetVisibilityStrategy("Always visible");
    await expect(dropdown).toBeVisible();
    await expect(dropdown.getByTestId('conditions-editor')).toBeHidden();
});

test(`Can use the editor to add or delete conditions on a question`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("three-short-text-questions.json");
    await form.goto(info.getId());

    // Add conditions on the third question
    await form.doInitVisibilityConditionsDropdown(2);
    await form.doSetVisibilityStrategy("Visible if...");
    await form.doFillStringCondition(
        0, "And", "My second question", "Is not equal to", "I love GLPI"
    );
    await form.doAddNewCondition();
    await form.doFillStringCondition(
        1, "Or", "My first question", "Contains", "GLPI is great"
    );

    // Save and reload, then verify both conditions are persisted
    await form.doSaveFormEditorAndReload();
    await form.doOpenQuestionConditionEditor(2);

    const conditions = form.getVisibleConditions();
    await expect(form.getConditionTarget(conditions.nth(0)))
        .toHaveText("Questions - My second question");
    await expect(form.getConditionValueOperator(conditions.nth(0)))
        .toHaveText("Is not equal to");
    await expect(form.getTextConditionValue(conditions.nth(0)))
        .toHaveValue("I love GLPI");

    await expect(form.getConditionLogicOperator(conditions.nth(1)))
        .toHaveText("Or");
    await expect(form.getConditionTarget(conditions.nth(1)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(conditions.nth(1)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(conditions.nth(1)))
        .toHaveValue("GLPI is great");

    // Delete the first condition
    await form.doDeleteCondition(0);
    await expect(form.getConditionTarget(conditions.nth(0)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(conditions.nth(0)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(conditions.nth(0)))
        .toHaveValue("GLPI is great");
    await expect(conditions).toHaveCount(1);

    // Save and reload, then verify only one condition remains
    await form.doSaveFormEditorAndReload();
    await form.doOpenQuestionConditionEditor(2);

    const final_conditions = form.getVisibleConditions();
    await expect(form.getConditionTarget(final_conditions.nth(0)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(final_conditions.nth(0)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(final_conditions.nth(0)))
        .toHaveValue("GLPI is great");
    await expect(final_conditions).toHaveCount(1);
});

test(`Can use the editor to add or delete conditions on a question (unsaved form)`, async ({
    page,
    profile,
    formImporter,
}) => {
    // Repeat the same process as the previous test but skip the saveAndReload
    // step to see how GLPI's handle conditions on unsaved questions.
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("three-short-text-questions.json");
    await form.goto(info.getId());

    // Add conditions on the third question
    await form.doInitVisibilityConditionsDropdown(2);
    await form.doSetVisibilityStrategy("Visible if...");
    await form.doFillStringCondition(
        0, "And", "My second question", "Is not equal to", "I love GLPI"
    );
    await form.doAddNewCondition();
    await form.doFillStringCondition(
        1, "Or", "My first question", "Contains", "GLPI is great"
    );

    const conditions = form.getVisibleConditions();
    await expect(form.getConditionTarget(conditions.nth(0)))
        .toHaveText("Questions - My second question");
    await expect(form.getConditionValueOperator(conditions.nth(0)))
        .toHaveText("Is not equal to");
    await expect(form.getTextConditionValue(conditions.nth(0)))
        .toHaveValue("I love GLPI");

    await expect(form.getConditionLogicOperator(conditions.nth(1)))
        .toHaveText("Or");
    await expect(form.getConditionTarget(conditions.nth(1)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(conditions.nth(1)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(conditions.nth(1)))
        .toHaveValue("GLPI is great");

    // Delete the first condition
    await form.doDeleteCondition(0);
    await expect(form.getConditionTarget(conditions.nth(0)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(conditions.nth(0)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(conditions.nth(0)))
        .toHaveValue("GLPI is great");
    await expect(conditions).toHaveCount(1);

    // Save and reload, then verify only one condition remains
    await form.doSaveFormEditorAndReload();
    await form.doOpenQuestionConditionEditor(2);

    const unsaved_final_conditions = form.getVisibleConditions();
    await expect(form.getConditionTarget(unsaved_final_conditions.nth(0)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(unsaved_final_conditions.nth(0)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(unsaved_final_conditions.nth(0)))
        .toHaveValue("GLPI is great");
    await expect(unsaved_final_conditions).toHaveCount(1);
});

test(`Can use the editor to add or delete conditions on a comment`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("two-questions-one-comment.json");
    await form.goto(info.getId());

    // Add conditions on the comment
    await form.doInitCommentVisibilityConditionsDropdown(0);
    await form.doSetVisibilityStrategy("Visible if...");
    await form.doFillStringCondition(
        0, "And", "My second question", "Contains", "I love GLPI"
    );
    await form.doAddNewCondition();
    await form.doFillStringCondition(
        1, "Or", "My first question", "Contains", "GLPI is great"
    );

    // Save and reload, then verify both conditions are persisted
    await form.doSaveFormEditorAndReload();
    await form.doOpenCommentConditionEditor(0);

    const comment_conditions = form.getVisibleConditions();
    await expect(form.getConditionTarget(comment_conditions.nth(0)))
        .toHaveText("Questions - My second question");
    await expect(form.getConditionValueOperator(comment_conditions.nth(0)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(comment_conditions.nth(0)))
        .toHaveValue("I love GLPI");

    await expect(form.getConditionLogicOperator(comment_conditions.nth(1)))
        .toHaveText("Or");
    await expect(form.getConditionTarget(comment_conditions.nth(1)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(comment_conditions.nth(1)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(comment_conditions.nth(1)))
        .toHaveValue("GLPI is great");

    // Delete the first condition
    await form.doDeleteCondition(0);
    await expect(form.getConditionTarget(comment_conditions.nth(0)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(comment_conditions.nth(0)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(comment_conditions.nth(0)))
        .toHaveValue("GLPI is great");
    await expect(comment_conditions).toHaveCount(1);

    // Save and reload, then verify only one condition remains
    await form.doSaveFormEditorAndReload();
    await form.doOpenCommentConditionEditor(0);

    const comment_final_conditions = form.getVisibleConditions();
    await expect(form.getConditionTarget(comment_final_conditions.nth(0)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(comment_final_conditions.nth(0)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(comment_final_conditions.nth(0)))
        .toHaveValue("GLPI is great");
    await expect(comment_final_conditions).toHaveCount(1);
});

test(`Can use the editor to add or delete conditions on a section`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("two-sections-three-questions.json");
    await form.goto(info.getId());

    // Add conditions on the second section
    await form.doInitSectionVisibilityConditionsDropdown(1);
    await form.doSetVisibilityStrategy("Visible if...");
    await form.doFillStringCondition(
        0, "And", "My second question", "Do not contains", "I love GLPI"
    );
    await form.doAddNewCondition();
    await form.doFillStringCondition(
        1, "Or", "My first question", "Contains", "GLPI is great"
    );

    // Save and reload, then verify both conditions are persisted
    await form.doSaveFormEditorAndReload();
    await form.doOpenSectionConditionEditor(1);

    const conditions = form.getVisibleConditions();
    await expect(form.getConditionTarget(conditions.nth(0)))
        .toHaveText("Questions - My second question");
    await expect(form.getConditionValueOperator(conditions.nth(0)))
        .toHaveText("Do not contains");
    await expect(form.getTextConditionValue(conditions.nth(0)))
        .toHaveValue("I love GLPI");

    await expect(form.getConditionLogicOperator(conditions.nth(1)))
        .toHaveText("Or");
    await expect(form.getConditionTarget(conditions.nth(1)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(conditions.nth(1)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(conditions.nth(1)))
        .toHaveValue("GLPI is great");

    // Delete the first condition
    await form.doDeleteCondition(0);
    await expect(form.getConditionTarget(conditions.nth(0)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(conditions.nth(0)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(conditions.nth(0)))
        .toHaveValue("GLPI is great");
    await expect(conditions).toHaveCount(1);

    // Save and reload, then verify only one condition remains
    await form.doSaveFormEditorAndReload();
    await form.doOpenSectionConditionEditor(1);

    const final_conditions = form.getVisibleConditions();
    await expect(form.getConditionTarget(final_conditions.nth(0)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(final_conditions.nth(0)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(final_conditions.nth(0)))
        .toHaveValue("GLPI is great");
    await expect(final_conditions).toHaveCount(1);
});

test(`Can use the editor to add or delete conditions on a destination`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("three-short-text-questions.json");

    // Go to the destination tab
    await form.gotoDestinationTab(info.getId());

    // Add conditions to the default destination
    await form.doOpenDestinationConditionEditor();
    await form.doSetVisibilityStrategy("Created if...");
    await form.doFillStringCondition(
        0, "And", "My second question", "Is not equal to", "I love GLPI"
    );
    await form.doAddNewCondition();
    await form.doFillStringCondition(
        1, "Or", "My first question", "Contains", "GLPI is great"
    );

    // Save destination and reopen condition editor
    await form.doSaveDestination();
    await form.doOpenDestinationConditionEditor();

    // Verify both conditions are persisted
    const conditions = form.getVisibleConditions();
    await expect(form.getConditionTarget(conditions.nth(0)))
        .toHaveText("Questions - My second question");
    await expect(form.getConditionValueOperator(conditions.nth(0)))
        .toHaveText("Is not equal to");
    await expect(form.getTextConditionValue(conditions.nth(0)))
        .toHaveValue("I love GLPI");

    await expect(form.getConditionLogicOperator(conditions.nth(1)))
        .toHaveText("Or");
    await expect(form.getConditionTarget(conditions.nth(1)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(conditions.nth(1)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(conditions.nth(1)))
        .toHaveValue("GLPI is great");

    // Delete the first condition
    await form.doDeleteCondition(0);
    await expect(form.getConditionTarget(conditions.nth(0)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(conditions.nth(0)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(conditions.nth(0)))
        .toHaveValue("GLPI is great");
    await expect(conditions).toHaveCount(1);

    // Save destination, reopen and verify only one condition remains
    await form.doSaveDestination();
    await form.doOpenDestinationConditionEditor();

    const dest_final_conditions = form.getVisibleConditions();
    await expect(form.getConditionTarget(dest_final_conditions.nth(0)))
        .toHaveText("Questions - My first question");
    await expect(form.getConditionValueOperator(dest_final_conditions.nth(0)))
        .toHaveText("Contains");
    await expect(form.getTextConditionValue(dest_final_conditions.nth(0)))
        .toHaveValue("GLPI is great");
    await expect(dest_final_conditions).toHaveCount(1);
});

test(`Conditions count badge is updated when conditions are added or removed`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("two-questions-one-comment.json");
    await form.goto(info.getId());

    const question = form.getNthQuestion(1);
    const badge = question.getByRole('status', { name: 'Conditions count' });

    // Initialize visibility configuration with "Visible if..." strategy
    await form.doInitVisibilityConditionsDropdown(1);
    await form.doSetVisibilityStrategy("Visible if...");
    await question.getByTitle('Configure visibility').filter({ visible: true }).click();
    await expect(badge).toHaveText('0');

    // Add first condition → badge = 1
    await question.getByTitle('Configure visibility').filter({ visible: true }).click();
    await form.doFillStringCondition(
        0, "And", "My first question", "Is equal to", "Expected answer 1"
    );
    await question.getByTitle('Configure visibility').filter({ visible: true }).click();
    await expect(badge).toHaveText('1');

    // Add second condition → badge = 2
    await question.getByTitle('Configure visibility').filter({ visible: true }).click();
    await form.doAddNewCondition();
    await form.doFillStringCondition(
        1, "And", "My first question", "Is equal to", "Expected answer 2"
    );
    await question.getByTitle('Configure visibility').filter({ visible: true }).click();
    await expect(badge).toHaveText('2');

    // Delete first condition → badge = 1
    await question.getByTitle('Configure visibility').filter({ visible: true }).click();
    await form.doDeleteCondition(0);
    await question.getByTitle('Configure visibility').filter({ visible: true }).click();
    await expect(badge).toHaveText('1');

    // Save and reload, verify persistence
    await form.doSaveFormEditorAndReload();

    const question_after = form.getNthQuestion(1);
    const badge_after = question_after.getByRole('status', { name: 'Conditions count' });
    await question_after.click();
    await expect(badge_after).toHaveText('1');

    // Delete last condition → badge = 0
    await form.doOpenQuestionConditionEditor(1);
    await form.doDeleteCondition(0);
    await question_after.getByTitle('Configure visibility').filter({ visible: true }).click();
    await expect(badge_after).toHaveText('0');
});

test(`Conditions count badge is updated when conditions are added or removed in form destination`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("active-form.json");

    // Go to the destination tab
    await form.gotoDestinationTab(info.getId());

    const badge = page.getByRole('status', { name: 'Conditions count' });

    // Set strategy to "Created if..." and close → badge = 0
    await form.doOpenDestinationConditionEditor();
    await form.doSetVisibilityStrategy("Created if...");
    await form.doOpenDestinationConditionEditor();
    await expect(badge).toHaveText('0');

    // Add first condition → badge = 1
    await form.doOpenDestinationConditionEditor();
    await form.doFillStringCondition(
        0, "And", "What is your name?", "Is equal to", "Expected answer 1"
    );
    await form.doOpenDestinationConditionEditor();
    await expect(badge).toHaveText('1');

    // Add second condition → badge = 2
    await form.doOpenDestinationConditionEditor();
    await form.doAddNewCondition();
    await form.doFillStringCondition(
        1, "And", "What is your name?", "Is equal to", "Expected answer 2"
    );
    await form.doOpenDestinationConditionEditor();
    await expect(badge).toHaveText('2');

    // Delete first condition → badge = 1
    await form.doOpenDestinationConditionEditor();
    await form.doDeleteCondition(0);
    await form.doOpenDestinationConditionEditor();
    await expect(badge).toHaveText('1');

    // Save destination and reload, verify persistence
    await form.doSaveDestination();
    await page.reload();

    const badge_after = page.getByRole('status', { name: 'Conditions count' });

    await form.doOpenDestinationConditionEditor();
    await form.doOpenDestinationConditionEditor();
    await expect(badge_after).toHaveText('1');

    // Delete remaining condition → badge = 0
    await form.doOpenDestinationConditionEditor();
    await form.doDeleteCondition(0);
    await form.doOpenDestinationConditionEditor();
    await expect(badge_after).toHaveText('0');
});

test(`Can duplicate section with conditions`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("sections-with-conditions.json");
    await form.goto(info.getId());

    // Duplicate the second section (which has a "visible_if" condition)
    const second_section = form.getRegion('Form section').nth(1);
    await second_section.getByRole('button', { name: 'More actions' }).click();
    await page.getByRole('button', { name: 'Duplicate section' }).click();

    // Save and reload
    await form.doSaveFormEditorAndReload();

    // Click on the 3rd section (the duplicate) to reveal its details
    const third_section = form.getRegion('Form section').nth(2);
    await third_section.click();

    // Verify the duplicated section has the "Visible if..." visibility badge
    await expect(
        third_section.getByLabel('Visible if...').first()
    ).toBeVisible();
});
