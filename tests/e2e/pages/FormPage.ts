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

import { expect, Locator, Page } from "@playwright/test";
import { GlpiPage } from "./GlpiPage";

export class FormPage extends GlpiPage
{
    public readonly editor_active_checkbox: Locator;
    public readonly editor_save_button: Locator;
    public readonly editor_save_success_alert: Locator;
    public readonly editor_form_header: Locator;

    public constructor(page: Page)
    {
        super(page);

        // Define locators
        this.editor_active_checkbox    = this.getCheckbox("Active");
        this.editor_save_button        = this.getButton("Save");
        this.editor_save_success_alert = page.getByRole('alert').filter({ hasText: 'Item successfully updated' });
        this.editor_form_header        = this.getRichTextByLabel("Form description");
    }

    public async goto(id: number): Promise<void>
    {
        const tab = "Glpi\\Form\\Form$main";
        await this.page.goto(`/front/form/form.form.php?id=${id}&forcetab=${tab}`);
    }

    public async addSection(name: string): Promise<Locator>
    {
        await this.getButton("Add a section").click();

        // eslint-disable-next-line playwright/no-raw-locators
        const focusedInput = this.page.locator('[title="Section name"]:focus');
        await focusedInput.waitFor({ state: 'visible', timeout: 5000 });

        const sectionIndex = await focusedInput.evaluate((input) => {
            const section = input.closest('section[aria-label="Form section"]');
            if (!section) {
                throw new Error('Section container not found');
            }
            const allSections = Array.from(document.querySelectorAll('section[aria-label="Form section"]'));
            return allSections.indexOf(section);
        });

        await focusedInput.fill(name);

        return this.getRegion('Form section').nth(sectionIndex);
    }

    public async addQuestion(name: string): Promise<Locator>
    {
        await this.getButton("Add a question").click();

        // eslint-disable-next-line playwright/no-raw-locators
        const focusedInput = this.page.getByRole('textbox', { name: 'Question name' }).and(this.page.locator(':focus'));
        await focusedInput.waitFor({ state: 'visible', timeout: 5000 });

        const questionIndex = await focusedInput.evaluate((input) => {
            const question = input.closest('section[aria-label="Question details"]');
            if (!question) {
                throw new Error('Question container not found');
            }
            const allQuestions = Array.from(document.querySelectorAll('section[aria-label="Question details"]'));
            return allQuestions.indexOf(question);
        });

        await focusedInput.fill(name);

        return this.getRegion('Question details').nth(questionIndex);
    }

    public async setQuestionType(question: Locator, type: string): Promise<void>
    {
        await this.doSetDropdownValue(
            this.getDropdownByLabel('Question type', question)
                .filter({visible : true}),
            type,
            false
        );
    }

    public async addComment(name: string): Promise<Locator>
    {
        await this.getButton("Add a comment").click();

        // eslint-disable-next-line playwright/no-raw-locators
        const focusedInput = this.page.getByRole('textbox', { name: 'Comment title' }).and(this.page.locator(':focus'));
        await focusedInput.waitFor({ state: 'visible', timeout: 5000 });

        const commentIndex = await focusedInput.evaluate((input) => {
            const comment = input.closest('section[aria-label="Comment details"]');
            if (!comment) {
                throw new Error('Comment container not found');
            }
            const allComments = Array.from(document.querySelectorAll('section[aria-label="Comment details"]'));
            return allComments.indexOf(comment);
        });

        await focusedInput.fill(name);

        return this.getRegion('Comment details').nth(commentIndex);
    }

    public async doSetActive(): Promise<void>
    {
        await this.editor_active_checkbox.check();
    }

    public async doSaveFormEditor(): Promise<void>
    {
        await this.editor_save_button.click();
        await expect(this.editor_save_success_alert).toBeVisible();
    }

    public async doSaveFormEditorAndReload(): Promise<void>
    {
        await this.doSaveFormEditor();
        await this.page.reload();
    }

    public async doOpenSubmitButtonConditions(): Promise<void>
    {
        await this.getConfigureVisiblityButton().click();
    }

    public async doInitVisibilityConditionsDropdown(
        question_index: number
    ): Promise<void> {
        await this.getButton("More actions").nth(question_index).click();
        await this.getButton("Configure visibility").click();
    }

    public async doOpenVisibilityConditionsConfiguration(): Promise<void> {
        await this.page.getByTestId('form-editor-left-panel')
            .getByTitle("Configure visibility")
            .filter({visible: true})
            .click()
        ;
    }

    public async doOpenQuestionConditionEditor(
        question_index: number
    ): Promise<void> {
        const question = this.getNthQuestion(question_index);
        await question.click();
        await question.getByTitle('Configure visibility')
            .filter({ visible: true })
            .click()
        ;
    }

    public async doInitCommentVisibilityConditionsDropdown(
        comment_index: number
    ): Promise<void> {
        const comment = this.getRegion('Comment details').nth(comment_index);
        await comment.click();
        await comment.getByRole('button', { name: 'More actions' })
            .filter({ visible: true })
            .click()
        ;
        await this.getButton('Configure visibility').click();
    }

    public async doOpenCommentConditionEditor(
        comment_index: number
    ): Promise<void> {
        const comment = this.getRegion('Comment details').nth(comment_index);
        await comment.click();
        await comment.getByTitle('Configure visibility')
            .filter({ visible: true })
            .click()
        ;
    }

    public async doDeleteCondition(index: number): Promise<void>
    {
        await this.getVisibleConditions()
            .nth(index)
            .getByRole('button', { name: 'Delete criteria' })
            .click()
        ;
    }

    public async doInitSectionVisibilityConditionsDropdown(
        section_index: number
    ): Promise<void> {
        const section = this.getRegion('Section details').nth(section_index);
        await section.click();
        await section.getByRole('button', { name: 'More actions' })
            .filter({ visible: true })
            .click()
        ;
        await this.getButton('Configure visibility').click();
    }

    public async doOpenSectionConditionEditor(
        section_index: number
    ): Promise<void> {
        const section = this.getRegion('Section details').nth(section_index);
        await section.click();
        await section.getByTitle('Configure visibility')
            .filter({ visible: true })
            .click()
        ;
    }

    public async doSetVisibilityStrategy(strategy: string): Promise<void>
    {
        // eslint-disable-next-line playwright/no-raw-locators
        await this.page.locator('label', { hasText: strategy })
            .filter({visible : true})
            .click()
        ;
    }

    public async doAddNewCondition(): Promise<void>
    {
        await this.getButton('Add another criteria').click();
    }

    public async doFillStringCondition(
        index: number,
        logic: string,
        item: string,
        operator: string,
        value: string,
    ): Promise<void> {
        await this.doFillConditionWithoutValue(index, logic, item, operator);
        await this.getTextbox('Value')
            .last()
            .fill(value)
        ;
    }

    public async doFillNumberCondition(
        index: number,
        logic: string,
        item: string,
        operator: string,
        value: number,
    ): Promise<void> {
        await this.doFillConditionWithoutValue(index, logic, item, operator);
        await this.getSpinButton('Value')
            .last().fill(
                value.toString()
            )
        ;
    }

    public async doFillDateCondition(
        index: number,
        logic: string,
        item: string,
        operator: string,
        value: string,
    ): Promise<void> {
        await this.doFillConditionWithoutValue(index, logic, item, operator);
        await this.page.getByTestId('conditions-container')
            .getByLabel('Value', {exact: true})
            .filter({visible: true})
            .last()
            .fill(value)
        ;
    }

    public async doFillDropdownCondition(
        index: number,
        logic: string,
        item: string,
        operator: string,
        value: string,
    ): Promise<void> {
        await this.doFillConditionWithoutValue(index, logic, item, operator);
        await this.doSetDropdownValue(
            this.getDropdownByLabel('Value')
                .filter({visible : true})
                .last(),
            value,
            false
        );
    }

    public async doFillMultipleDropdownCondition(
        index: number,
        logic: string,
        item: string,
        operator: string,
        value: string[],
    ): Promise<void> {
        await this.doFillConditionWithoutValue(index, logic, item, operator);
        for (const option of value) {
            await this.doSetDropdownValue(
                this.getDropdownByLabel('Value')
                    .filter({visible : true})
                    .last(),
                option,
                false
            );
        }
    }

    public async doFillConditionWithoutValue(
        index: number,
        logic: string,
        item: string,
        operator: string,
    ): Promise<void> {
        // Set logic operator
        if (index !== 0) {
            await this.doSetDropdownValue(
                this.getDropdownByLabel('Logic operator')
                    .filter({visible : true})
                    .nth(index - 1),
                logic,
            );
        }

        // Set target item
        await this.doSetDropdownValue(
            this.getDropdownByLabel('Item')
                .filter({visible : true})
                .nth(index),
            item,
        );

        // Set operator
        await this.doSetDropdownValue(
            this.getDropdownByLabel('Value operator').last(),
            operator,
        );
    }

    public getConditions(): Locator
    {
        return this.page.getByTestId('visibility-condition');
    }

    public getVisibleConditions(): Locator
    {
        return this.getConditions().filter({ visible: true });
    }

    public getConditionLogicOperator(condition: Locator): Locator
    {
        return this.getDropdownByLabel(
            'Logic operator',
            condition,
        );
    }

    public getConditionTarget(condition: Locator): Locator
    {
        return this.getDropdownByLabel(
            'Item',
            condition,
        );
    }

    public getConditionValueOperator(condition: Locator): Locator
    {
        return this.getDropdownByLabel(
            'Value operator',
            condition,
        );
    }

    public getTextConditionValue(condition: Locator): Locator
    {
        return condition.getByRole('textbox', {
            name: "Value",
            exact: true,
        }).filter({visible: true});
    }

    public getLastSection(): Locator
    {
        return this.getRegion("Form section").last();
    }

    public getNthQuestion(index: number): Locator
    {
        return this.getRegion("Question details").nth(index);
    }

    public getLastQuestion(): Locator
    {
        return this.getRegion("Question details").last();
    }

    public getLastComment(): Locator
    {
        return this.getRegion("Comment details").last();
    }

    public getConfigureVisiblityButton(): Locator
    {
        return this.getRegion('Form properties accordion')
            .getByTitle("Configure visibility")
        ;
    }

    public getConfigureVisiblityButtonDisplayedValue(): Locator
    {
        return this.getConfigureVisiblityButton()
            .getByTestId("active-visibility-setting")
            .filter({
                visible: true
            })
        ;
    }

    public getVisiblityConditionDropdown(question: Locator)
    {
        return question.getByTestId('visibility-dropdown');
    }

    public async getFormHeader(): Promise<Locator>
    {
        // Initialize rich text if not done yet
        await this.initRichTextByLabel("Form description");
        return this.editor_form_header;
    }

    public async getSectionDescription(section: Locator): Promise<Locator>
    {
        // Initialize rich text if not done yet
        await this.initRichTextByLabel("Section description", section);
        return this.getRichTextByLabel("Section description", section);
    }

    public async getQuestionDescription(question: Locator): Promise<Locator>
    {
        // Ensure question is focused
        await question.getByTitle('Question name').click();

        // Initialize rich text if not done yet
        await this.initRichTextByLabel("Question description", question);

        return this.getRichTextByLabel("Question description", question);
    }

    public async getCommentDescription(comment: Locator): Promise<Locator>
    {
        // Initialize rich text if not done yet
        await this.initRichTextByLabel("Comment description", comment);
        return this.getRichTextByLabel("Comment description", comment);
    }

    public async gotoDestinationTab(id: number): Promise<void>
    {
        const tab = "Glpi\\Form\\Destination\\FormDestination$1";
        await this.page.goto(
            `/front/form/form.form.php?id=${id}&forcetab=${tab}`
        );
    }

    public async doOpenDestinationConditionEditor(): Promise<void>
    {
        await this.page.getByTitle('Configure creation conditions').click();
    }

    public async doSaveDestination(): Promise<void>
    {
        await this.getButton('Update item').click();
        await expect(
            this.page.getByRole('alert').filter({ hasText: 'Item successfully updated' })
        ).toBeVisible();
        await this.getButton('Close').click();
    }

    public async doOpenDestinationAccordionItem(item_label: string): Promise<void>
    {
        const accordion = this.getRegion('Destination fields accordion');
        const button = accordion.getByRole('button', { name: item_label });
        await button.click();
        await expect(accordion.getByRole('region', { name: item_label })).toBeVisible();
    }

    public async doSaveDestinationAndReopenAccordion(item_label: string): Promise<void>
    {
        await this.getButton('Update item').click();
        await expect(
            this.page.getByRole('alert').filter({ hasText: 'Item successfully updated' })
        ).toBeVisible();
        await this.doOpenDestinationAccordionItem(item_label);
    }

    public getStrategyDropdown(config: Locator): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators
        return config.getByTestId('strategy-dropdown').first().locator('+ span').getByRole('combobox');
    }

    public async doChangeQuestionType(
        question: Locator,
        new_type: string,
    ): Promise<void> {
        await question.getByRole('textbox', { name: 'Question name' }).click();
        await this.doSetDropdownValue(
            this.getDropdownByLabel('Question type', question),
            new_type,
            false,
        );
        // eslint-disable-next-line playwright/no-raw-locators
        await question.locator('[data-glpi-loading="true"]').waitFor({ state: 'detached' });
    }

    public async doInitValidationConfiguration(
        question_index: number
    ): Promise<void> {
        await this.getButton('More actions').nth(question_index).click();
        await this.getButton('Configure validation').click();
    }

    public async doOpenValidationConditionEditor(
        question_index: number
    ): Promise<void> {
        const question = this.getNthQuestion(question_index);
        await question.click();
        await question.getByTitle('Configure validation')
            .filter({ visible: true })
            .click()
        ;
    }

    public async doCloseValidationConditionEditor(
        question_index: number
    ): Promise<void> {
        const question = this.getNthQuestion(question_index);
        await question.getByTitle('Configure validation')
            .filter({ visible: true })
            .click()
        ;
    }

    private static readonly VALIDATION_STRATEGY_MAP: Record<string, string> = {
        'No validation': 'no_validation',
        'Valid if...': 'valid_if',
        'Invalid if...': 'invalid_if',
    };

    public async doSetValidationStrategy(strategy: string): Promise<void>
    {
        const container = this.page.getByTestId('validation-dropdown-container')
            .filter({ visible: true });

        const radio = container.getByRole('radio', { name: strategy, exact: true });

        // Wait for the JS controller to be ready (removes pointer-events: none)
        await expect(radio).not.toHaveAttribute('data-glpi-conditions-editor-disabled');

        const strategy_value = FormPage.VALIDATION_STRATEGY_MAP[strategy];
        await container.getByTestId(`strategy-label-${strategy_value}`).click();
    }

    public async doFillValidationCondition(
        index: number,
        logic: string | null,
        operator: string,
        value: string,
    ): Promise<void> {
        const condition = this.getVisibleValidationConditions().nth(index);

        if (logic !== null && index > 0) {
            await this.doSetDropdownValue(
                this.getDropdownByLabel('Logic operator', condition),
                logic,
            );
        }

        await this.doSetDropdownValue(
            this.getDropdownByLabel('Value operator', condition),
            operator,
        );

        await condition.getByRole('textbox', { name: 'Value' }).fill(value);
    }

    public async doAddValidationCondition(): Promise<void>
    {
        await this.getButton('Add another criteria').click();
    }

    public async doDeleteValidationCondition(index: number): Promise<void>
    {
        await this.getVisibleValidationConditions()
            .nth(index)
            .getByRole('button', { name: 'Delete criteria' })
            .click()
        ;
    }

    public getValidationConditions(): Locator
    {
        return this.page.getByTestId('validation-condition');
    }

    public getVisibleValidationConditions(): Locator
    {
        return this.getValidationConditions().filter({ visible: true });
    }

    public getValidationConditionValueOperator(condition: Locator): Locator
    {
        return this.getDropdownByLabel('Value operator', condition);
    }

    public getValidationConditionTextValue(condition: Locator): Locator
    {
        return condition.getByRole('textbox', {
            name: 'Value',
            exact: true,
        }).filter({ visible: true });
    }

    public getValidationConditionLogicOperator(condition: Locator): Locator
    {
        return this.getDropdownByLabel('Logic operator', condition);
    }

    public getValidationConditionsCountBadge(
        question_index: number,
    ): Locator {
        return this.getNthQuestion(question_index)
            .getByRole('status', { name: 'Conditions count' })
            .filter({ visible: true })
        ;
    }

    public async doPreviewForm(): Promise<void>
    {
        const preview_link = this.page.getByRole('link', { name: /Preview/ })
            .filter({ visible: true });
        const href = await preview_link.getAttribute('href');
        if (href === null) {
            throw new Error('Preview link has no href');
        }
        await this.page.goto(href);
    }

    public getValidationErrorMessage(textbox: Locator): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators
        return textbox.locator('..').getByTestId('validation-error-message');
    }

    public async doAddDropdownOptions(
        question: Locator,
        labels: string[]
    ): Promise<void> {
        for (const label of labels) {
            await question.getByRole('textbox', { name: 'Selectable option' }).last().fill(label);
        }
    }

    public async doSelectSingleDropdownOption(
        question: Locator,
        option: string
    ): Promise<void> {
        await this.doSetDropdownValue(
            this.getDropdownByLabel('Default option', question),
            option
        );
    }

    public async doToggleMultipleDropdownOption(
        question: Locator,
        option: string
    ): Promise<void> {
        const dropdown = this.getDropdownByLabel('Default options', question);
        await dropdown.click();
        await this.page
            .getByRole('listbox')
            .getByRole('option', { name: option, exact: true })
            .click();
    }

    public async doSelectMultipleDropdownOption(
        question: Locator,
        option: string
    ): Promise<void> {
        await this.doToggleMultipleDropdownOption(question, option);
        await expect(
            this.getDropdownByLabel('Default options', question)
        ).toContainText(option);
    }

    public async doDeselectMultipleDropdownOption(
        question: Locator,
        option: string
    ): Promise<void> {
        await this.doToggleMultipleDropdownOption(question, option);
        await expect(
            this.getDropdownByLabel('Default options', question)
        ).not.toContainText(option);
    }

    public async doEnableMultipleDropdownMode(
        question: Locator
    ): Promise<void> {
        await question.getByRole('checkbox', { name: 'Allow multiple options' }).check();
    }

    public async doDisableMultipleDropdownMode(
        question: Locator
    ): Promise<void> {
        await question.getByRole('checkbox', { name: 'Allow multiple options' }).uncheck();
    }

    public async doFillVisibilityCondition(
        item: string,
        operator: string,
        value: string,
    ): Promise<void> {
        const item_dropdown = this.getDropdownByLabel('Item')
            .filter({ visible: true }).first();
        const response_promise = this.page.waitForResponse(
            (r) => r.url().includes('/Form/Condition/') && r.ok()
        );
        await this.doSetDropdownValue(item_dropdown, item);
        await response_promise;

        const operator_dropdown = this.getDropdownByLabel('Value operator')
            .filter({ visible: true }).last();
        await operator_dropdown.waitFor({ state: 'visible' });
        await this.doSetDropdownValue(operator_dropdown, operator);

        await this.doSetDropdownValue(
            this.getDropdownByLabel('Value').filter({ visible: true }).last(),
            value,
            false
        );
    }

    public getDropdownOptionInputs(question: Locator): Locator
    {
        return question.getByRole('textbox', { name: 'Selectable option' });
    }

    public getSingleDropdownDefault(question: Locator): Locator
    {
        return this.getDropdownByLabel('Default option', question);
    }

    public getMultipleDropdownDefault(question: Locator): Locator
    {
        return this.getDropdownByLabel('Default options', question);
    }

    public async expectDropdownOptionLabels(
        question: Locator,
        labels: string[]
    ): Promise<void> {
        const textboxes = this.getDropdownOptionInputs(question);
        await expect(textboxes).toHaveCount(labels.length + 1);
        for (let i = 0; i < labels.length; i++) {
            await expect(textboxes.nth(i)).toHaveValue(labels[i]);
        }
        await expect(textboxes.last()).toHaveValue('');
    }

    public async expectSingleDropdownSelection(
        question: Locator,
        expected: string | null
    ): Promise<void> {
        const dropdown = this.getSingleDropdownDefault(question);
        if (expected === null) {
            await expect(dropdown).toContainText('-----');
        } else {
            await expect(dropdown).toContainText(expected);
        }
    }

    public async expectMultipleDropdownSelection(
        question: Locator,
        selected: string[],
        not_selected: string[]
    ): Promise<void> {
        const dropdown = this.getMultipleDropdownDefault(question);
        for (const label of selected) {
            await expect(dropdown).toContainText(label);
        }
        for (const label of not_selected) {
            await expect(dropdown).not.toContainText(label);
        }
    }
}
