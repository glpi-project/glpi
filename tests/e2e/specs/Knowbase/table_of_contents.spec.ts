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
import { TicketPage } from "../../pages/TicketPage";
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from "../../utils/WorkerEntities";

const filler = `<p>${'Lorem ipsum dolor sit amet. '.repeat(120)}</p>`;
const answer = `<h2>Installation</h2>${filler}<h3>Requirements</h3>${filler}<h4>Details</h4>${filler}<h2>Usage</h2>${filler}`;

/**
 * A ticket with one computer per article, each computer linked to its article:
 * the Items tab of the ticket embeds each article in a FAQ popup.
 */
async function createTicketWithArticles(
    api: { createItem: (itemtype: string, fields: Record<string, unknown>) => Promise<number> },
    articles: Record<string, string>,
): Promise<number> {
    const entities_id = getWorkerEntityId();
    const ticket_id = await api.createItem('Ticket', {
        name: 'TOC embedded articles',
        content: 'Content',
        entities_id: entities_id,
    });
    for (const [name, article_answer] of Object.entries(articles)) {
        const computer_id = await api.createItem('Computer', { name: name, entities_id: entities_id });
        const article_id = await api.createItem('KnowbaseItem', {
            name: name,
            entities_id: entities_id,
            answer: article_answer,
        });
        await api.createItem('KnowbaseItem_Item', {
            knowbaseitems_id: article_id,
            itemtype: 'Computer',
            items_id: computer_id,
        });
        await api.createItem('Item_Ticket', {
            tickets_id: ticket_id,
            itemtype: 'Computer',
            items_id: computer_id,
        });
    }
    return ticket_id;
}

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
        await expect(kb.toc).toBeHidden();

        await kb.doToggleTableOfContents();
        await expect(kb.toc.getByRole('link')).toHaveText(['Installation', 'Requirements', 'Usage']);
        await expect(kb.tocToggle).toHaveAttribute('aria-pressed', 'true');
        await expect(kb.tocToggle).not.toHaveAttribute('aria-expanded');

        await page.reload();
        await expect(kb.toc.getByRole('link')).toHaveText(['Installation', 'Requirements', 'Usage']);
    });

    test('Scrolls to a heading and highlights it', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC navigation',
            entities_id: getWorkerEntityId(),
            answer: answer,
        });

        await kb.goto(id);
        await kb.doToggleTableOfContents();
        await kb.getTocLink('Usage').click();

        await expect(page).toHaveURL(/#h-usage$/);
        await expect(kb.getHeading('Usage')).toBeInViewport();
        await expect(kb.getTocLink('Usage')).toHaveAttribute('aria-current', 'location');
        await expect(kb.getTocLink('Installation')).not.toHaveAttribute('aria-current');
    });

    test('Opens at the heading of the URL hash', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC hash',
            entities_id: getWorkerEntityId(),
            answer: answer,
        });

        await kb.goto(id);
        await kb.doToggleTableOfContents();
        await kb.gotoHeading(id, 'h-requirements');

        await expect(kb.getHeading('Requirements')).toBeInViewport();
        await expect(kb.getTocLink('Requirements')).toHaveAttribute('aria-current', 'location');
    });

    test('Opens at the heading of a link shared before the current ids', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC legacy hash',
            entities_id: getWorkerEntityId(),
            answer: `${answer}<h2>2. Setup</h2>${filler}<h2>Привет</h2>${filler}<h2 id="custom-target">Custom</h2>${filler}`,
        });

        await kb.gotoHeading(id, 'requirements');
        await expect(kb.getHeading('Requirements')).toBeInViewport();
        await expect(page).toHaveURL(/#h-requirements$/);

        await kb.gotoHeading(id, 'slug_2.-setup');
        await expect(kb.getHeading('2. Setup')).toBeInViewport();
        await expect(page).toHaveURL(/#h-2-setup$/);

        await kb.gotoHeading(id, 'privet');
        await expect(kb.getHeading('Привет')).toBeInViewport();

        // A heading deeper than the outline keeps its link.
        await kb.gotoHeading(id, 'h-details');
        await expect(kb.getHeading('Details')).toBeInViewport();

        // So does a heading with an id given by the author.
        await kb.gotoHeading(id, 'custom-target');
        await expect(kb.getHeading('Custom')).toBeInViewport();
        await expect(page).toHaveURL(/#h-custom$/);
    });

    test('Toggles from the keyboard shortcut', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC shortcut',
            entities_id: getWorkerEntityId(),
            answer: answer,
        });

        await kb.goto(id);
        await expect(kb.tocToggle).toBeVisible();

        await kb.doPressTableOfContentsShortcut();
        await expect(kb.toc).toBeVisible();
        await expect(kb.tocToggle).toHaveAttribute('aria-pressed', 'true');

        await kb.doPressTableOfContentsShortcut();
        await expect(kb.toc).toBeHidden();
        await expect(kb.tocToggle).toHaveAttribute('aria-pressed', 'false');

        // A field outside the article keeps the keys.
        await page.getByPlaceholder('Search…').focus();
        await kb.doPressTableOfContentsShortcut();
        await expect(kb.toc).toBeHidden();
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
        await expect(kb.tocToggle).toBeHidden();
        await expect(kb.tocEmptyMessage).toBeHidden();

        await kb.editor.enterEditMode();
        await expect(kb.tocEmptyMessage).toBeVisible();
    });

    test('Updates while editing and saves the content unchanged', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'TOC edit',
            entities_id: getWorkerEntityId(),
            answer: answer,
        });

        await kb.goto(id);
        await kb.doToggleTableOfContents();
        await kb.editor.enterEditMode();

        await kb.getHeading('Usage').click();
        await page.keyboard.press('End');
        await page.keyboard.type(' guide');

        await expect(kb.toc.getByRole('link')).toHaveText(['Installation', 'Requirements', 'Usage guide']);

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
        // The name of the link must not be part of the heading name.
        await kb.doCopyHeadingLink('Requirements');

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
        await expect(kb.tocToggle).not.toHaveAttribute('aria-pressed');
        await kb.doToggleTableOfContents();
        await expect(kb.tocToggle).toHaveAttribute('aria-expanded', 'true');

        await expect(kb.toc).toHaveCount(1);
        await kb.getTocLink('Usage').click();

        await expect(page).toHaveURL(/#h-usage$/);
        await expect(kb.toc).toBeHidden();

        // Back to a toggle button, without the menu state.
        await page.setViewportSize({ width: 1600, height: 800 });
        await expect(kb.tocToggle).toHaveAttribute('aria-pressed', 'false');
        await expect(kb.tocToggle).not.toHaveAttribute('aria-expanded');
    });

    test('Toggles only its own list when a page shows several articles', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const ticket_id = await createTicketWithArticles(api, {
            'TOC first article': answer,
            'TOC second article': answer,
        });

        await new TicketPage(page).goto(ticket_id, 'Item_Ticket$1');
        await kb.doOpenEmbeddedArticle();

        // The FAQ popup is narrow: the shortcut opens the list in a dropdown.
        await kb.doPressTableOfContentsShortcut();
        await expect(kb.toc).toBeVisible();
        await expect(page.getByRole('button', { name: 'Table of contents', includeHidden: true }).last())
            .toHaveAttribute('aria-expanded', 'false');
    });

    test('Highlights the heading reached in a FAQ popup', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        // Short enough for the popup, not the article, to scroll.
        const short_filler = `<p>${'Lorem ipsum dolor sit amet. '.repeat(6)}</p>`;
        const ticket_id = await createTicketWithArticles(api, {
            'TOC popup article': ['Alpha', 'Beta', 'Gamma', 'Delta']
                .map((heading) => `<h2>${heading}</h2>${short_filler}`)
                .join(''),
        });

        await new TicketPage(page).goto(ticket_id, 'Item_Ticket$1');
        await kb.doOpenEmbeddedArticle();
        await kb.doScrollToHeading('Beta');

        // Opening the list would scroll the popup back to its button.
        await expect(kb.getClosedTocLink('Beta')).toHaveAttribute('aria-current', 'location');
    });
});
