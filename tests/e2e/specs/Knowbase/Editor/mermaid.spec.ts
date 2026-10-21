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

/** Stored form of a diagram, with its source already HTML-escaped. */
const mermaidAnswer = (source: string) => `<pre><code class="language-mermaid">${source}</code></pre>`;

const DIAGRAM_ANSWER = mermaidAnswer('flowchart TD\n    A[Start] --&gt; B[Finish]');

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
        // A `/` in the source must not open the slash menu.
        await kb.editor.typeText('    C --> D["Done /u"]');
        await kb.slashMenu.assertHidden();

        await source_tab.press('ArrowLeft');
        await expect(page.getByRole('tab', { name: 'Diagram' })).toBeFocused();
        await expect(page.getByRole('figure', { name: 'Diagram' })).toContainText('Done /u');
    });

    test('Typing ```mermaid inserts a diagram, not a code block', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid input rule',
            entities_id: getWorkerEntityId(),
            answer: '<p>Content</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.clearContent();

        await kb.editor.typeText('```mermaid ');
        await expect(page.getByRole('tab', { name: 'Source' })).toHaveAttribute('aria-selected', 'true');
        await kb.editor.typeText('flowchart TD\n    A --> Typed');
        await page.getByRole('tab', { name: 'Diagram' }).click();
        await expect(page.getByRole('figure', { name: 'Diagram' })).toContainText('Typed');
    });

    test('Diagram opens on its source after its insertion only', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid inserted then restored',
            entities_id: getWorkerEntityId(),
            answer: '<p>Content</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.clearContent();

        await kb.slashMenu.open();
        await kb.slashMenu.selectByClick('Diagram');
        await expect(page.getByRole('tab', { name: 'Source' })).toHaveAttribute('aria-selected', 'true');

        const diagram_tab = page.getByRole('tab', { name: 'Diagram' });
        const diagram = page.getByRole('figure', { name: 'Diagram' });
        await diagram_tab.click();
        await diagram.click();
        await kb.editor.pressKey('Delete');
        await expect(diagram).toHaveCount(0);

        // Undo builds the block view again.
        await kb.editor.pressKey('Control+z');
        await expect(diagram).toBeVisible();
        await expect(diagram_tab).toHaveAttribute('aria-selected', 'true');
    });

    test('Each diagram of an article is drawn once', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        // A drawn figure starts a new scan while the next diagrams are still being drawn.
        const labels = ['One', 'Two', 'Three', 'Four'];
        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid many diagrams',
            entities_id: getWorkerEntityId(),
            answer: labels.map((label) => mermaidAnswer(`flowchart TD\n    A --&gt; ${label}`)).join(''),
        });

        await kb.goto(id);
        const diagrams = page.getByRole('figure', { name: 'Diagram' });
        // The diagrams are drawn one after the other, which is slow on a loaded runner.
        await expect(diagrams.filter({ hasText: 'Four' })).toBeVisible({ timeout: 15000 });
        await expect(diagrams).toHaveCount(labels.length);
    });

    test('Diagram directive cannot change the styles', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const directive = '%%{init: {&quot;themeCSS&quot;: &quot;.nodeLabel {display: none}&quot;}}%%';
        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid directive',
            entities_id: getWorkerEntityId(),
            answer: mermaidAnswer(`${directive}\nflowchart TD\n    A[Start] --&gt; B[Finish]`),
        });

        await kb.goto(id);
        await expect(page.getByRole('figure', { name: 'Diagram' }).getByText('Finish')).toBeVisible();
    });

    test('Diagram follows a theme change made without a page reload', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid theme change',
            entities_id: getWorkerEntityId(),
            answer: DIAGRAM_ANSWER,
        });

        await kb.goto(id);
        const label = page.getByRole('figure', { name: 'Diagram' }).getByText('Finish');
        await expect(label).toHaveCSS('color', 'rgb(51, 51, 51)');

        // As the theme switcher of the debug bar does.
        await page.evaluate(() => {
            document.documentElement.dataset.glpiThemeDark = '1';
        });
        // The edit mode draws the diagram again.
        await kb.editor.enterEditMode();
        await expect(label).toHaveCSS('color', 'rgb(204, 204, 204)');
    });

    test('Invalid diagram shows the error in edit mode and its source in read mode', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid invalid',
            entities_id: getWorkerEntityId(),
            answer: mermaidAnswer('not a diagram'),
        });

        await kb.goto(id);
        await expect(page.getByText('not a diagram')).toBeVisible();
        await expect(page.getByRole('figure', { name: 'Diagram' })).toHaveCount(0);
        // Mermaid must not leave its own error drawing in the page.
        await expect(page.getByText('Syntax error in text')).toHaveCount(0);

        await kb.editor.enterEditMode();
        await expect(page.getByRole('tabpanel', { name: 'Diagram' })).toContainText(/error|not a diagram/i);

        await kb.editor.save();
        await expect(page.getByText('not a diagram', { exact: true })).toBeVisible();
        await expect(page.getByRole('tabpanel', { name: 'Diagram' })).toBeHidden();
    });

    test('Only the flowchart, sequence and state diagram types are drawn', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid diagram types',
            entities_id: getWorkerEntityId(),
            answer: [
                mermaidAnswer('sequenceDiagram\n    Alice-&gt;&gt;Bob: Sequence label'),
                mermaidAnswer('stateDiagram-v2\n    [*] --&gt; StateLabel'),
                mermaidAnswer('gantt\n    section Gantt\n    Task: 2024-01-01, 1d'),
            ].join(''),
        });

        await kb.goto(id);
        const diagrams = page.getByRole('figure', { name: 'Diagram' });
        // The diagrams are drawn one after the other, which is slow on a loaded runner.
        await expect(diagrams.filter({ hasText: 'Sequence label' })).toBeVisible({ timeout: 15000 });
        await expect(diagrams.filter({ hasText: 'StateLabel' })).toBeVisible();
        await expect(diagrams).toHaveCount(2);
        await expect(page.getByText('section Gantt')).toBeVisible();
    });

    test('Unsupported diagram type shows a message in edit mode', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid unsupported type',
            entities_id: getWorkerEntityId(),
            answer: mermaidAnswer('gantt\n    section Gantt\n    Task: 2024-01-01, 1d'),
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await expect(page.getByRole('tabpanel', { name: 'Diagram' })).toContainText('Unsupported diagram type');
    });

    test('Backspace and Delete keep the text and the diagram source apart', async ({ page, profile, api }) => {
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

        await page.getByRole('tab', { name: 'Source' }).click();
        await kb.placeCaretInEditMode('flowchart TD', 'start');
        await kb.editor.pressKey('Backspace');
        await expect(page.getByText('Before', { exact: true })).toBeVisible();
        await kb.placeCaretInEditMode('B[Finish]', 'end');
        await kb.editor.pressKey('Delete');
        await expect(page.getByText('After', { exact: true })).toBeVisible();
        await page.getByRole('tab', { name: 'Diagram' }).click();
        await expect(diagram).toContainText('Finish');

        await kb.editor.save();
        await expect(page.getByText('Before', { exact: true })).toBeVisible();
        await expect(page.getByText('After', { exact: true })).toBeVisible();
        await expect(diagram).toContainText('Finish');
    });

    test('Delete removes an empty diagram, as Backspace does', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid empty delete',
            entities_id: getWorkerEntityId(),
            answer: `<p>Before</p>${mermaidAnswer('')}`,
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        const source_tab = page.getByRole('tab', { name: 'Source' });
        await source_tab.click();
        await kb.editor.pressKey('Delete');
        await expect(source_tab).toHaveCount(0);
        await expect(page.getByText('Before', { exact: true })).toBeVisible();
    });

    test('Revision diff shows the changed diagram source with its change marks', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        // Old and new labels merge into a valid diagram (`A --> BC`) if the diff is drawn.
        const diagram = (target: string) => mermaidAnswer(`flowchart TD\n    A --&gt; ${target}`);
        // The diagrams are drawn in order: once the unchanged one is drawn, the changed one was handled.
        const unchanged = mermaidAnswer('flowchart TD\n    Kept');
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

    test('Diagram source gets an absolute link to the article when the content is used in a ticket', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Mermaid content fallback',
            entities_id: getWorkerEntityId(),
            answer: `<p>Intro</p>${DIAGRAM_ANSWER}`,
        });

        const response = await page.request.get(`/Knowbase/KnowbaseItem/${id}/Content`);
        const html = await response.text();
        expect(html).toContain('See the diagram in the article');
        // Through the redirect page, which opens the article in the interface of the reader.
        expect(html).toMatch(new RegExp(`href="https?://[^"]+/index\\.php\\?redirect=knowbaseitem_${id}"`));
        expect(html).toContain('flowchart TD');
    });
});
