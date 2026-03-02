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
}
