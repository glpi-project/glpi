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

import { expect, test } from "../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../pages/KnowbaseItemPage";
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from "../../utils/WorkerEntities";

const filler = `<p>${'Lorem ipsum dolor sit amet. '.repeat(120)}</p>`;
const answer = `<h2>Installation</h2>${filler}<h3>Requirements</h3>${filler}<h4>Details</h4>${filler}<h2>Usage</h2>${filler}`;

test.describe('Knowledge base table of contents', () => {
    test.beforeEach(async ({ profile }) => {
        await profile.set(Profiles.SuperAdmin);
    });

    test('Lists the headings in a column that stays shown', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC column',
            entities_id: getWorkerEntityId(),
            answer: answer,
        });

        await kb.goto(id);
        const toc = page.getByRole('navigation', { name: 'Table of contents' });
        await expect(toc).toBeHidden();

        const toggle = page.getByRole('button', { name: 'Table of contents' });
        await toggle.click();
        await expect(toc.getByRole('link')).toHaveText(['Installation', 'Requirements', 'Usage']);
        await expect(page.getByRole('button', { name: 'Table of contents' })).toHaveAttribute('aria-pressed', 'true');

        await page.reload();
        await expect(toc.getByRole('link')).toHaveText(['Installation', 'Requirements', 'Usage']);
    });

    test('Scrolls to a heading and highlights it', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC navigation',
            entities_id: getWorkerEntityId(),
            answer: answer,
        });

        await kb.goto(id);
        await page.getByRole('button', { name: 'Table of contents' }).click();
        const toc = page.getByRole('navigation', { name: 'Table of contents' });
        await toc.getByRole('link', { name: 'Usage' }).click();

        await expect(page).toHaveURL(/#h-usage$/);
        await expect(page.getByRole('heading', { name: 'Usage' })).toBeInViewport();
        await expect(toc.getByRole('link', { name: 'Usage' })).toHaveAttribute('aria-current', 'location');
        await expect(toc.getByRole('link', { name: 'Installation' })).not.toHaveAttribute('aria-current');
    });

    test('Opens at the heading of the URL hash', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC hash',
            entities_id: getWorkerEntityId(),
            answer: answer,
        });

        await kb.goto(id);
        await page.getByRole('button', { name: 'Table of contents' }).click();
        await page.goto(`/front/knowbaseitem.form.php?id=${id}#h-requirements`);

        await expect(page.getByRole('heading', { name: 'Requirements' })).toBeInViewport();
        await expect(
            page.getByRole('navigation', { name: 'Table of contents' }).getByRole('link', { name: 'Requirements' })
        ).toHaveAttribute('aria-current', 'location');
    });

    test('Opens at the heading of a link shared before the current ids', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC legacy hash',
            entities_id: getWorkerEntityId(),
            answer: `${answer}<h2>2. Setup</h2>${filler}<h2>Привет</h2>${filler}`,
        });

        await kb.goto(id);
        await page.goto(`/front/knowbaseitem.form.php?id=${id}#requirements`);
        await expect(page.getByRole('heading', { name: 'Requirements' })).toBeInViewport();
        await expect(page).toHaveURL(/#h-requirements$/);

        await page.goto(`/front/knowbaseitem.form.php?id=${id}#slug_2.-setup`);
        await expect(page.getByRole('heading', { name: '2. Setup' })).toBeInViewport();
        await expect(page).toHaveURL(/#h-2-setup$/);

        await page.goto(`/front/knowbaseitem.form.php?id=${id}#privet`);
        await expect(page.getByRole('heading', { name: 'Привет' })).toBeInViewport();

        // A heading deeper than the outline keeps its link.
        await page.goto(`/front/knowbaseitem.form.php?id=${id}#h-details`);
        await expect(page.getByRole('heading', { name: 'Details' })).toBeInViewport();
    });

    test('Toggles from the actions menu and the keyboard shortcut', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC switch',
            entities_id: getWorkerEntityId(),
            answer: answer,
        });

        await kb.goto(id);
        const toc = page.getByRole('navigation', { name: 'Table of contents' });
        await expect(page.getByRole('button', { name: 'Table of contents' })).toBeVisible();

        await kb.articleActionsMenu.click();
        await kb.getButton('Show table of contents').click();
        await expect(toc).toBeVisible();
        await expect(kb.getButton('Show table of contents').getByRole('checkbox')).toBeChecked();

        await page.keyboard.press('Control+Alt+KeyH');
        await expect(toc).toBeHidden();
        await expect(kb.getButton('Show table of contents').getByRole('checkbox')).not.toBeChecked();
    });

    test('Hides the toggle and the column when the article has no headings', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC no headings',
            entities_id: getWorkerEntityId(),
            answer: '<p>No headings here</p>',
        });

        await page.addInitScript(() => localStorage.setItem('glpi_kb_toc_visible', '1'));
        await kb.goto(id);
        await expect(page.getByText('No headings here')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Table of contents' })).toBeHidden();
        await expect(page.getByText('This article has no headings.')).toBeHidden();

        await kb.editor.enterEditMode();
        await expect(page.getByText('This article has no headings.')).toBeVisible();
    });

    test('Updates while editing and saves the content unchanged', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC edit',
            entities_id: getWorkerEntityId(),
            answer: answer,
        });

        await kb.goto(id);
        await page.getByRole('button', { name: 'Table of contents' }).click();
        await kb.editor.enterEditMode();

        await page.getByRole('heading', { name: 'Usage' }).click();
        await page.keyboard.press('End');
        await page.keyboard.type(' guide');

        const toc = page.getByRole('navigation', { name: 'Table of contents' });
        await expect(toc.getByRole('link')).toHaveText(['Installation', 'Requirements', 'Usage guide']);

        await kb.editor.save();
        const saved = await api.getItem('KnowbaseItem', id);
        expect(saved.answer).toContain('Usage guide');
        expect(saved.answer).not.toContain('h-usage-guide');
        expect(saved.answer).not.toContain('kb-heading-anchor');
    });

    test('Copies the link of a heading', async ({ page, api }) => {
        await page.context().grantPermissions(['clipboard-read', 'clipboard-write']);
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC copy link',
            entities_id: getWorkerEntityId(),
            answer: answer,
        });

        await kb.goto(id);
        const heading = page.getByRole('heading', { name: 'Requirements' });
        await heading.hover();
        await heading.getByRole('link', { name: 'Copy link to this section' }).click();

        await expect(page.getByText('Copied to clipboard')).toBeVisible();
        const copied = await page.evaluate(() => navigator.clipboard.readText());
        expect(copied).toMatch(/knowbaseitem\.form\.php\?id=\d+.*#h-requirements$/);
    });

    test('Opens the list in a dropdown when the column has no space', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC narrow',
            entities_id: getWorkerEntityId(),
            answer: answer,
        });

        await page.setViewportSize({ width: 1100, height: 800 });
        await kb.goto(id);
        await page.getByRole('button', { name: 'Table of contents' }).click();

        const toc = page.getByRole('navigation', { name: 'Table of contents' });
        await expect(toc).toHaveCount(1);
        await toc.getByRole('link', { name: 'Usage' }).click();

        await expect(page).toHaveURL(/#h-usage$/);
        await expect(toc).toBeHidden();
    });
});
