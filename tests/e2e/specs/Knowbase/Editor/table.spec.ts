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

import { expect, test } from "../../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../../pages/KnowbaseItemPage";
import { Api } from "../../../utils/Api";
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";

const TABLE_3x2 = '<table><tbody>'
    + '<tr><td>A1</td><td>B1</td><td>C1</td></tr>'
    + '<tr><td>A2</td><td>B2</td><td>C2</td></tr>'
    + '</tbody></table>';

const TABLE_1x2 = '<table><tbody>'
    + '<tr><td>Only1</td></tr>'
    + '<tr><td>Only2</td></tr>'
    + '</tbody></table>';

async function createArticleWithTable(api: Api, name: string, answer: string): Promise<number> {
    return await api.createItem('KnowbaseItem', {
        name,
        entities_id: getWorkerEntityId(),
        answer,
    });
}

test.describe('Knowledge Base Editor - Table controls', () => {
    test('Table grips are not rendered in read mode', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await createArticleWithTable(api, 'Table read mode', TABLE_3x2);

        await kb.goto(id);
        await expect(kb.tableEditor.table).toBeVisible();
        await expect(kb.tableEditor.grips).toHaveCount(0);
    });

    test('Table grips are available in edit mode', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await createArticleWithTable(api, 'Table edit mode', TABLE_3x2);

        await kb.goto(id);
        await kb.editor.enterEditMode();

        await expect(kb.tableEditor.table.getByRole('button', { name: 'Select column 1' })).toBeAttached();
        await expect(kb.tableEditor.table.getByRole('button', { name: 'Select row 1' })).toBeAttached();
        await expect(kb.tableEditor.table.getByRole('button', { name: 'Select table' })).toBeAttached();
    });

    test('Deleting a column removes its cells', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await createArticleWithTable(api, 'Table delete column', TABLE_3x2);

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await expect(kb.tableEditor.firstRowCells).toHaveCount(3);

        await kb.tableEditor.deleteColumn(2);

        await expect(kb.tableEditor.firstRowCells).toHaveCount(2);
        await expect(kb.editor.contentContainer.getByText('B1', { exact: true })).toBeHidden();
        await expect(kb.editor.contentContainer.getByText('B2', { exact: true })).toBeHidden();
        await expect(kb.editor.contentContainer.getByText('A1', { exact: true })).toBeVisible();
        await expect(kb.editor.contentContainer.getByText('C1', { exact: true })).toBeVisible();
    });

    test('Selecting a column highlights only that column\'s cells', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await createArticleWithTable(api, 'Table select column', TABLE_3x2);

        await kb.goto(id);
        await kb.editor.enterEditMode();

        await kb.tableEditor.selectColumn(1);

        await expect(kb.tableEditor.cell('A1')).toHaveClass(/selectedCell/);
        await expect(kb.tableEditor.cell('A2')).toHaveClass(/selectedCell/);
        await expect(kb.tableEditor.cell('B1')).not.toHaveClass(/selectedCell/);
    });

    test('Inserting a column adds a column', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await createArticleWithTable(api, 'Table insert column', TABLE_3x2);

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await expect(kb.tableEditor.firstRowCells).toHaveCount(3);

        await kb.tableEditor.insertColumnAt(2);

        await expect(kb.tableEditor.firstRowCells).toHaveCount(4);
        await expect(kb.editor.contentContainer.getByText('A1', { exact: true })).toBeVisible();
        await expect(kb.editor.contentContainer.getByText('B1', { exact: true })).toBeVisible();
    });

    test('Deleting a row removes its cells', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await createArticleWithTable(api, 'Table delete row', TABLE_3x2);

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await expect(kb.tableEditor.rows).toHaveCount(2);

        await kb.tableEditor.deleteRow(2);

        await expect(kb.tableEditor.rows).toHaveCount(1);
        await expect(kb.editor.contentContainer.getByText('A2', { exact: true })).toBeHidden();
        await expect(kb.editor.contentContainer.getByText('A1', { exact: true })).toBeVisible();
    });

    test('Inserting a row adds a row', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await createArticleWithTable(api, 'Table insert row', TABLE_3x2);

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await expect(kb.tableEditor.rows).toHaveCount(2);

        await kb.tableEditor.insertRowAt(2);

        await expect(kb.tableEditor.rows).toHaveCount(3);
    });

    test('Deleting the whole table via the corner grip removes it', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await createArticleWithTable(api, 'Table delete whole', TABLE_3x2);

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await expect(kb.tableEditor.table).toBeVisible();

        await kb.tableEditor.deleteTable();

        await expect(kb.tableEditor.table).toHaveCount(0);
    });

    test('Deleting the last remaining column removes the whole table', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await createArticleWithTable(api, 'Table delete last column', TABLE_1x2);

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await expect(kb.tableEditor.firstRowCells).toHaveCount(1);

        await kb.tableEditor.deleteColumn(1);

        await expect(kb.tableEditor.table).toHaveCount(0);
    });
});
