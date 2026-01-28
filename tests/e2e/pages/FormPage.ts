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

    public constructor(page: Page)
    {
        super(page);

        // Define locators
        this.editor_active_checkbox    = this.getCheckbox("Active");
        this.editor_save_button        = this.getButton("Save");
        this.editor_save_success_alert = page.getByRole('alert');
    }

    public async goto(id: number): Promise<void>
    {
        const tab = "Glpi\\Form\\Form$main";
        await this.page.goto(`/front/form/form.form.php?id=${id}&forcetab=${tab}`);
    }

    public async doSetActive(): Promise<void>
    {
        await this.editor_active_checkbox.check();
    }

    public async doSaveFormEditor(): Promise<void>
    {
        await this.editor_save_button.click();
        await expect(this.editor_save_success_alert).toBeVisible();
        await expect(this.editor_save_success_alert).toContainText(
            'Item successfully updated'
        );
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

    public getLastQuestion(): Locator
    {
        return this.getRegion("Question details").last();
    }
}
