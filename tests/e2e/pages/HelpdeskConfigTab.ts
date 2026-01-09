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

export class HelpdeskHomeConfigTag extends GlpiPage
{
    public constructor(page: Page)
    {
        super(page);
    }

    public async goto(itemtype: string, id: number): Promise<void>
    {
        if (itemtype === 'Entity') {
            return this.gotoForEntity(id);
        } else if (itemtype === 'Profile') {
            return this.gotoForProfile(id);
        } else {
            throw new Error("Unsupported itemtype");
        }
    }

    public async gotoForEntity(entity_id: number): Promise<void>
    {
        await this.page.goto(
            `/front/entity.form.php?id=${entity_id}&forcetab=Entity$9`
        );
    }

    public async gotoForProfile(profile_id: number): Promise<void>
    {
        await this.page.goto(
            `/front/profile.form.php?id=${profile_id}&forcetab=Profile$4`
        );
    }

    public getTiles(): Locator
    {
        return this.page.getByTestId('config-tile');
    }

    public getTile(title: string): Locator
    {
        return this.page.getByTestId('config-tile').filter({
            has: this.page.getByRole('heading', {
                name: title,
                exact: true ,
            }),
        });
    }

    public getNewTileButton(): Locator
    {
        return this.getButton('Add tile').first();
    }

    public getTileTitles(): Locator
    {
        return this.getTiles().getByRole("heading");
    }

    public getUpdateConfirmationAlert(): Locator
    {
        return this.getAlert("Configuration updated successfully");
    }

    public async doDragAndDropTileAfterTile(
        source: string,
        destination: string
    ): Promise<void> {
        // eslint-disable-next-line playwright/no-raw-locators
        await this.getTiles()
            .filter({ hasText: source})
            .nth(0)
            .locator('[data-glpi-helpdesk-config-tile-handle]')
            .dragTo(
                this.getTiles().filter({ hasText: destination})
            )
        ;
    }

    public async doSaveTilesOrder(): Promise<void>
    {
        await this.getButton('Save tiles order').click();
        await expect(
            this.getAlert("Configuration updated successfully.")
        ).toBeVisible();
    }

    public async doDeleteActiveTile(): Promise<void>
    {
        await this.getButton('Delete tile').click();
        await expect(this.getUpdateConfirmationAlert()).toBeVisible();
    }

    public async doEditActiveTileTitle(title: string): Promise<void>
    {
        await this.getTextbox('Title').fill(title);
    }

    public async doEditActiveTileDescription(content: string): Promise<void>
    {
        await this.getRichTextByLabel("Description").fill(content);
    }

    public async doSetActiveGlpiPageTileTarget(page: string): Promise<void>
    {
        await this.doSetDropdownValue(
            this.getDropdownByLabel("Target page"),
            page,
        );
    }

    public async doSetActiveExternalPageTileUrl(url: string): Promise<void>
    {
        await this.getTextbox("Target url").fill(url);
    }

    public async doSetActiveFormTileForm(form: string): Promise<void>
    {
        await this.doSetDropdownValue(
            this.getDropdownByLabel("Target form"),
            form,
        );
    }

    public async doSetActiveTileType(type: string): Promise<void>
    {
        await this.doSetDropdownValue(
            this.getDropdownByLabel("Type"),
            type,
        );
    }

    public async doSaveActiveTile(): Promise<void>
    {
        await this.getButton('Save changes').click();
        await expect(this.getUpdateConfirmationAlert()).toBeVisible();
    }

    public async doAddTile(): Promise<void>
    {
        await this.page.getByRole('dialog').getByRole('button', {
            name: 'Add tile',
            exact: true,
        }).click();
        await expect(this.getUpdateConfirmationAlert()).toBeVisible();
    }
}
