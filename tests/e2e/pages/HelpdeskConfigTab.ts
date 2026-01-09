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

    public getNewTileButton(): Locator
    {
        return this.getButton('Add tile');
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

    public async expectTiles(names: string[]): Promise<void>
    {
        const tiles = this.getTiles();
        let i = 0;
        for (const name of names) {
            await expect(tiles.nth(i++).getByRole("heading")).toHaveText(name);
        }
    }
}
