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
import { randomUUID } from "crypto";

export class AssetDefinitionPage extends GlpiPage
{
    public readonly system_name_input: Locator;
    public readonly active_dropdown: Locator;
    public readonly add_button: Locator;
    public readonly save_button: Locator;

    public constructor(page: Page)
    {
        super(page);
        this.system_name_input = page.getByLabel("System name");
        this.active_dropdown = this.getDropdownByLabel('Active');
        this.add_button = this.getButton('Add');
        this.save_button = this.getButton('Save');
    }

    public async gotoCreationForm(): Promise<void>
    {
        await this.page.goto('/front/asset/assetdefinition.form.php');
    }

    public async goto(id: number, tab?: string): Promise<void>
    {
        let url = `/front/asset/assetdefinition.form.php?id=${id}`;
        if (tab) {
            url += `&forcetab=${tab}`;
        }
        await this.page.goto(url);
    }

    public async gotoAssetForm(class_name: string, id: number): Promise<void>
    {
        await this.page.goto(`/front/asset/asset.form.php?class=${class_name}&id=${id}&withtemplate=2`);
    }

    public async doCreateField(
        label: string,
        type: string,
        options: Map<string, string | boolean> = new Map<string, string | boolean>(),
    ): Promise<void> {
        await this.page.getByRole('button', { name: 'New field' }).click();

        const dialog = this.page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await expect(dialog).toHaveAttribute('data-cy-shown', 'true');

        await dialog.getByLabel('Label').fill(label);
        const type_dropdown = this.getDropdownByLabel('Type', dialog);
        await this.doSetDropdownValue(type_dropdown, type);

        if (options.has('item_type')) {
            const item_type_dropdown = this.getDropdownByLabel('Item type', dialog);
            await this.doSetDropdownValue(item_type_dropdown, options.get('item_type') as string);
        }
        if (options.has('min')) {
            await dialog.getByLabel('Minimum').fill(options.get('min') as string);
        }
        if (options.has('max')) {
            await dialog.getByLabel('Maximum').fill(options.get('max') as string);
        }
        if (options.has('step')) {
            await dialog.getByLabel('Step').fill(options.get('step') as string);
        }
        if (options.has('multiple_values')) {
            await dialog.getByLabel('Multiple values').check();
        }
        if (options.has('readonly')) {
            const readonly_dropdown = this.getDropdownByLabel('Readonly for these profiles', dialog);
            await this.doSetDropdownValue(readonly_dropdown, 'Super-Admin');
        }
        if (options.has('mandatory')) {
            await dialog.getByLabel('Mandatory').check();
        }
        if (options.has('enable_richtext')) {
            await dialog.getByLabel('Rich text').check();
        }
        if (options.has('enable_images')) {
            await dialog.getByLabel('Allow images').check();
        }

        await dialog.getByRole('button', { name: 'Add' }).click();
        await expect(dialog).not.toBeAttached();

        const key = `custom_${label.toLowerCase().replace(' ', '_')}`;
        await expect(this.page.getByTestId(`sortable-field-${key}`)).toBeVisible();
    }

    public static generateAssetName(): string
    {
        const suffix = randomUUID().replaceAll('-', '');
        return `customasset${suffix}`;
    }
}
