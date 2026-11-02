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

import { Page, Locator } from '@playwright/test';
import { TipTapEditorHelper } from './TipTapEditorHelper';

type DeleteKind = 'column' | 'row' | 'table';

// Drives the KB editor table grips. Indices and insert positions are 1-based,
// matching the accessible names the extension renders.
export class TableEditorHelper {
    private readonly page: Page;
    private readonly editor: TipTapEditorHelper;

    constructor(page: Page, editor: TipTapEditorHelper) {
        this.page = page;
        this.editor = editor;
    }

    get table(): Locator {
        return this.editor.contentContainer.getByRole('table');
    }

    get rows(): Locator {
        return this.table.getByRole('row');
    }

    get firstRowCells(): Locator {
        return this.rows.first().getByRole('cell');
    }

    // The cell containing `text`, resolved by role. Grips add no text content,
    // so cell text stays unique to the authored content.
    cell(text: string): Locator {
        return this.table.getByRole('cell').filter({ hasText: text });
    }

    get grips(): Locator {
        return this.table.getByRole('button', {
            name: /^(Select (column|row|table)|Insert (column|row) at position)/,
        });
    }

    deleteControl(kind: DeleteKind): Locator {
        const label = { column: 'Delete column', row: 'Delete row', table: 'Delete table' }[kind];
        return this.page.getByRole('button', { name: label, exact: true });
    }

    // Grips are inert zero-opacity widgets; the extension acts on mousedown and
    // resolves the target from event.target, so dispatch that event directly.
    private async clickControl(control: Locator): Promise<void> {
        await control.dispatchEvent('mousedown', { bubbles: true, cancelable: true, button: 0 });
    }

    async selectColumn(index: number): Promise<void> {
        await this.clickControl(this.table.getByRole('button', { name: `Select column ${index}`, exact: true }));
    }

    async selectRow(index: number): Promise<void> {
        await this.clickControl(this.table.getByRole('button', { name: `Select row ${index}`, exact: true }));
    }

    async selectTable(): Promise<void> {
        await this.clickControl(this.table.getByRole('button', { name: 'Select table', exact: true }));
    }

    async insertColumnAt(position: number): Promise<void> {
        await this.clickControl(
            this.table.getByRole('button', { name: `Insert column at position ${position}`, exact: true })
        );
    }

    async insertRowAt(position: number): Promise<void> {
        await this.clickControl(
            this.table.getByRole('button', { name: `Insert row at position ${position}`, exact: true })
        );
    }

    async deleteColumn(index: number): Promise<void> {
        await this.selectColumn(index);
        await this.deleteControl('column').click();
    }

    async deleteRow(index: number): Promise<void> {
        await this.selectRow(index);
        await this.deleteControl('row').click();
    }

    async deleteTable(): Promise<void> {
        await this.selectTable();
        await this.deleteControl('table').click();
    }
}
