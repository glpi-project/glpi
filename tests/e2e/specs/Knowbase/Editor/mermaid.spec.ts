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
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";

const DIAGRAM_ANSWER = '<pre><code class="language-mermaid">flowchart TD\n    A[Start] --&gt; B[Finish]</code></pre>';

test.describe('Knowledge Base Editor - Mermaid diagrams', () => {

    test('Stored diagram is drawn in read mode and survives an edit round trip', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid round trip',
            entities_id: getWorkerEntityId(),
            answer: DIAGRAM_ANSWER,
        });

        await kb.goto(id);
        const diagrams = page.getByRole('figure', { name: 'Diagram' });
        await expect(diagrams).toHaveCount(1);
        await expect(diagrams).toContainText('Start');
        await expect(page.getByText('flowchart TD')).toBeHidden();

        await kb.editor.enterEditMode();
        await expect(diagrams).toHaveCount(1);
        await expect(page.getByRole('tab', { name: 'Diagram' })).toHaveAttribute('aria-selected', 'true');

        await page.getByRole('tab', { name: 'Source' }).click();
        await expect(page.getByRole('tabpanel', { name: 'Source' })).toContainText('flowchart TD');

        await kb.editor.save();
        await expect(page.getByRole('tab', { name: 'Source' })).toBeHidden();
        await expect(diagrams).toHaveCount(1);

        await kb.goto(id);
        await expect(diagrams).toHaveCount(1);
        await expect(diagrams).toContainText('Finish');
    });

    test('Slash command inserts a diagram opened on its source', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid slash command',
            entities_id: getWorkerEntityId(),
            answer: '<p>Content</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.clearContent();

        await kb.slashMenu.open();
        await kb.slashMenu.selectByClick('Diagram');

        const source_tab = page.getByRole('tab', { name: 'Source' });
        await expect(source_tab).toHaveAttribute('aria-selected', 'true');

        await kb.editor.pressKey('Enter');
        await kb.editor.typeText('    C --> D[Done]');

        await source_tab.press('ArrowLeft');
        await expect(page.getByRole('tab', { name: 'Diagram' })).toBeFocused();
        await expect(page.getByRole('figure', { name: 'Diagram' })).toContainText('Done');
    });

    test('Invalid diagram shows the error in edit mode and its source in read mode', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid invalid',
            entities_id: getWorkerEntityId(),
            answer: '<pre><code class="language-mermaid">not a diagram</code></pre>',
        });

        await kb.goto(id);
        await expect(page.getByText('not a diagram')).toBeVisible();
        await expect(page.getByRole('figure', { name: 'Diagram' })).toHaveCount(0);

        await kb.editor.enterEditMode();
        await expect(page.getByRole('tabpanel', { name: 'Diagram' })).toContainText(/error|not a diagram/i);

        await kb.editor.save();
        await expect(page.getByText('not a diagram', { exact: true })).toBeVisible();
        await expect(page.getByRole('tabpanel', { name: 'Diagram' })).toBeHidden();
    });

    test('Backspace and Delete next to a diagram keep the text out of its source', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid keyboard',
            entities_id: getWorkerEntityId(),
            answer: `<p>Before</p>${DIAGRAM_ANSWER}<p>After</p>`,
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        const diagram = page.getByRole('figure', { name: 'Diagram' });
        await expect(diagram).toContainText('Finish');

        await kb.placeCaretInEditMode('After', 'start');
        await kb.editor.pressKey('Backspace');
        await expect(page.getByText('After', { exact: true })).toBeVisible();
        await expect(diagram).toContainText('Finish');

        await kb.placeCaretInEditMode('Before', 'end');
        await kb.editor.pressKey('Delete');
        await expect(page.getByText('Before', { exact: true })).toBeVisible();
        await expect(diagram).toContainText('Finish');

        await kb.editor.save();
        await expect(page.getByText('Before', { exact: true })).toBeVisible();
        await expect(page.getByText('After', { exact: true })).toBeVisible();
        await expect(diagram).toContainText('Finish');
    });

    test('Revision diff shows the changed diagram source with its change marks', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        // Old and new labels merge into a valid diagram (`A --> BC`) if the diff is drawn.
        const diagram = (target: string) => `<pre><code class="language-mermaid">flowchart TD\n    A --&gt; ${target}</code></pre>`;
        // The diagrams are drawn in order: once the unchanged one is drawn, the changed one was handled.
        const unchanged = '<pre><code class="language-mermaid">flowchart TD\n    Kept</code></pre>';
        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid revision diff',
            entities_id: getWorkerEntityId(),
            answer: diagram('B') + unchanged,
        });
        await api.updateItem('KnowbaseItem', id, { answer: diagram('C') + unchanged });

        await kb.goto(id);
        await kb.articleActionsMenu.click();
        await kb.getButton('History').click();
        await page.getByTestId('history-event').nth(1).click();

        const content = kb.editor.contentContainer;
        const diagrams = content.getByRole('figure', { name: 'Diagram' });
        await expect(page.getByRole('article')).toHaveClass(/kb-article--diff-mode/);
        await expect(diagrams.filter({ hasText: 'Kept' })).toBeVisible();
        await expect(diagrams).toHaveCount(1);
        await expect(content.getByRole('deletion')).toHaveText('B');
        await expect(content.getByRole('insertion')).toHaveText('C');
        await expect(content.getByRole('deletion')).toBeVisible();
    });

    test('Diagram becomes a link to the article when the content is used in a ticket', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid content fallback',
            entities_id: getWorkerEntityId(),
            answer: `<p>Intro</p>${DIAGRAM_ANSWER}`,
        });

        const response = await page.request.get(`/Knowbase/KnowbaseItem/${id}/Content`);
        const html = await response.text();
        expect(html).toContain('See the diagram in the article');
        expect(html).toContain(`knowbaseitem.form.php?id=${id}`);
        expect(html).not.toContain('flowchart TD');
    });
});
