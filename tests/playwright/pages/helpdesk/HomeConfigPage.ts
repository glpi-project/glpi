/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

import { type Locator, type Page } from '@playwright/test';
import { GlpiPage } from './../GlpiPage';

/**
 * POM for the helpdesk home page configuration.
 * See https://playwright.dev/docs/pom.
 */
export class HomeConfigPage extends GlpiPage
{
    private readonly tilesArea: Locator;
    private readonly editionArea: Locator;
    private readonly insertionArea: Locator;
    private readonly typeDropdown: Locator;
    private readonly titleField: Locator;
    private readonly descriptionField: Locator;
    private readonly targetPageDropdown: Locator;
    private readonly targetUrlField: Locator;
    private readonly targetFormDropdown: Locator;
    private readonly cancelEditionButton: Locator;
    private readonly confirmEditionButton: Locator;
    private readonly addNewTileButton: Locator;
    private readonly submitNewTileButton: Locator;
    private readonly cancelNewTileButton: Locator;

    public constructor(page: Page) {
        super(page);

        this.tilesArea = page.getByRole('region', {'name': "Home tiles configuration"});
        this.editionArea = page.getByRole('region', {'name': "Edit tile"});
        this.insertionArea = page.getByRole('region', {'name': "Add a new tile"});
        this.typeDropdown = this.getDropdownByLabel("Type");
        this.titleField = page.getByRole('textbox', {name: "Title"});
        this.descriptionField = this.getRichTextByLabel("Description");
        this.targetPageDropdown = this.getDropdownByLabel("Target page");
        this.targetUrlField = page.getByRole('textbox', {name: "Target url"});
        this.targetFormDropdown = this.getDropdownByLabel("Target form");
        this.cancelEditionButton = page.getByRole('button', {name: "Cancel"});
        this.confirmEditionButton = page.getByRole('button', {name: "Save changes"});
        this.addNewTileButton = page.getByRole('button', {name: "Add tile"});
        this.submitNewTileButton = page.getByRole('button', {name: "Add tile"});
        this.cancelNewTileButton = page.getByRole('button', {name: "Cancel"});
    }

    public async goto(profile_id: number) {
        await this.page.goto(`/front/profile.form.php?id=${profile_id}&forcetab=Profile$4`);
    }

    public getTile(name: string): Locator {
        return this.page.getByRole('region', {name: name});
    }

    public async deleteTile(name: string) {
        await this.getTile(name).getByTitle("Show more actions").click();
        await this.page.getByRole('button', {name: "Delete tile"}).click();
    }

    public async goToEditTile(name: string) {
        await this.getTile(name).getByTitle("Show more actions").click();
        await this.page.getByRole('button', {name: "Edit tile"}).click();
    }

    public async setTileType(type: string) {
        await this.setDropdownValue(this.typeDropdown, type);
    }

    public async setTileTitle(title: string) {
        await this.titleField.fill(title);
    }

    public async setTileDescription(description: string) {
        await this.descriptionField.fill(description);
    }

    public async setGlpiTileTargetPage(page: string) {
        await this.setDropdownValue(this.targetPageDropdown, page);
    }

    public async setExternalTileTargetUrl(url: string) {
        await this.targetUrlField.fill(url);
    }

    public async setFormTileTargetForm(form: string) {
        await this.setDropdownValue(this.targetFormDropdown, form);
    }

    public async saveTileChanges() {
        await this.confirmEditionButton.click();
    }

    public async submitNewTile() {
        await this.submitNewTileButton.click();
    }

    public async cancelNewTile() {
        await this.cancelNewTileButton.click();
    }

    public async cancelEdition() {
        await this.cancelEditionButton.click();
    }

    public async addNewTile() {
        await this.addNewTileButton.click();
    }

    public getTilesArea() {
        return this.tilesArea;
    }

    public getEditionArea() {
        return this.editionArea;
    }

    public getInsertionArea() {
        return this.insertionArea;
    }
}
