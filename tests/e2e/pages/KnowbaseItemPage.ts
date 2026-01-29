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

import { Locator, Page } from "@playwright/test";
import { GlpiPage } from "./GlpiPage";
import { TipTapEditorHelper } from "../utils/TipTapEditorHelper";
import { SlashMenuHelper } from "../utils/SlashMenuHelper";
import { BubbleMenuHelper } from "../utils/BubbleMenuHelper";

export class KnowbaseItemPage extends GlpiPage
{
    private _editorHelper: TipTapEditorHelper | null = null;
    private _slashMenuHelper: SlashMenuHelper | null = null;
    private _bubbleMenuHelper: BubbleMenuHelper | null = null;

    public constructor(page: Page)
    {
        super(page);
    }

    public get editor(): TipTapEditorHelper
    {
        if (!this._editorHelper) {
            this._editorHelper = new TipTapEditorHelper(this.page);
        }
        return this._editorHelper;
    }

    public get slashMenu(): SlashMenuHelper
    {
        if (!this._slashMenuHelper) {
            this._slashMenuHelper = new SlashMenuHelper(this.page, this.editor);
        }
        return this._slashMenuHelper;
    }

    public get bubbleMenu(): BubbleMenuHelper
    {
        if (!this._bubbleMenuHelper) {
            this._bubbleMenuHelper = new BubbleMenuHelper(this.page, this.editor);
        }
        return this._bubbleMenuHelper;
    }

    public async goto(id: number): Promise<void>
    {
        await this.page.goto(
            `/front/knowbaseitem.form.php?id=${id}&forcetab=KnowbaseItem$1`
        );
    }

    public async doToggleFaqStatus(): Promise<void>
    {
        const faq_toggle = this.getButton('Add to FAQ');
        const response_promise = this.page.waitForResponse(
            response => response.url().includes('/ToggleField')
        );
        await faq_toggle.click();
        await response_promise;
    }

    public getCommentByContent(content: string): Locator
    {
        return this.page.getByText(content).filter({
            'visible': true,
        });
    }
}
