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

import { expect, type Locator, type Page } from '@playwright/test';
import path from 'path';
import { GlpiPage } from './GlpiPage';

export class NetworkEquipmentModelPage extends GlpiPage
{
    public readonly ports_count_input: Locator;

    public constructor(page: Page)
    {
        super(page);
        this.ports_count_input = this.getSpinButton('Set number of ports');
    }

    public async goto(id: number, tab?: string): Promise<void>
    {
        let url = `/front/networkequipmentmodel.form.php?id=${id}`;
        if (tab) {
            url += `&forcetab=${tab}`;
        }
        await this.page.goto(url);
    }

    public async doUploadFrontImage(file: string): Promise<void>
    {
        await this.page.getByTestId('file-upload-picture_front')
            .setInputFiles(path.join(__dirname, `../../fixtures/${file}`))
        ;
        const progress = this.page.getByRole('progressbar');
        await expect(progress).toHaveText('Upload successful');
        await expect(progress).not.toBeAttached();
    }

    public async doSaveForm(): Promise<void>
    {
        await this.getButton('Save').click();
    }

    public async doSetPortCount(count: number): Promise<void>
    {
        await this.ports_count_input.fill(String(count));
        await this.getButton('Add').click();
        await expect(this.getPortButton('1')).toBeVisible({ timeout: 15000 });
    }

    /**
     * Sets the cropper selection position for the currently active port editor.
     * Must be called after a port button has been clicked and the zone data
     * panel is visible.
     */
    public async doSetCropperSelection(
        x: number,
        y: number,
        w: number,
        h: number
    ): Promise<void>
    {
        await this.page.waitForFunction(() => {
            const img = document.querySelector('.cropper-container > img') as any;
            return img?.cropper?.getCropperSelection?.() !== undefined;
        });
        await this.page.evaluate(
            ({ x, y, w, h }) => {
                const img = document.querySelector('.cropper-container > img') as any;
                img.cropper.getCropperSelection().$change(x, y, w, h);
            },
            { x, y, w, h }
        );
    }

    public async doConfigurePort(
        port_name: string,
        x: number,
        y: number,
        w: number,
        h: number,
    ): Promise<void>
    {
        await this.getPortButton(port_name).click();
        await expect(this.getButton('Save port data')).toBeVisible();
        await this.doSetCropperSelection(x, y, w, h);
        await this.getButton('Save port data').click();
        await expect(this.getPortButton(port_name)).toHaveClass(/btn-success/);
    }

    public async doConfigurePortWithLabel(
        port_name: string,
        label: string,
        x: number,
        y: number,
        w: number,
        h: number,
    ): Promise<void>
    {
        await this.getPortButton(port_name).click();
        await expect(this.getButton('Save port data')).toBeVisible();
        await this.getTextbox('Port Label').fill(label);
        await this.doSetCropperSelection(x, y, w, h);
        await this.getButton('Save port data').click();
        await expect(this.getPortButton(label)).toHaveClass(/btn-success/);
    }

    public async doResetPort(port_name: string): Promise<void>
    {
        await this.getPortButton(port_name).click();
        await expect(this.getButton('Reset')).toBeVisible();
        await this.getButton('Reset').click();
    }

    public async doAddZone(): Promise<void>
    {
        await this.getButton('Add zone').click();
        // Move mouse away to dismiss any Bootstrap tooltip that could block nearby buttons
        await this.page.mouse.move(0, 400);
    }

    public async doRemoveZone(): Promise<void>
    {
        await this.getButton('Remove zone').click();
    }

    public getPortButton(name: string): Locator
    {
        return this.page.getByRole('button', {
            name: name,
            exact: true,
        }).filter({ visible: true });
    }
}
