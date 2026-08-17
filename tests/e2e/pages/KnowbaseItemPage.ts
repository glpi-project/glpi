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
import path from 'path';
import { GlpiPage } from "./GlpiPage";
import { TipTapEditorHelper } from "../utils/TipTapEditorHelper";
import { SlashMenuHelper } from "../utils/SlashMenuHelper";
import { BubbleMenuHelper } from "../utils/BubbleMenuHelper";
import { TableEditorHelper } from "../utils/TableEditorHelper";
import { ReadModeCommentBubbleHelper } from "../utils/ReadModeCommentBubbleHelper";

export class KnowbaseItemPage extends GlpiPage
{
    private _editorHelper: TipTapEditorHelper | null = null;
    private _slashMenuHelper: SlashMenuHelper | null = null;
    private _bubbleMenuHelper: BubbleMenuHelper | null = null;
    private _tableEditorHelper: TableEditorHelper | null = null;
    private _readModeCommentBubbleHelper: ReadModeCommentBubbleHelper | null = null;

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

    public get tableEditor(): TableEditorHelper
    {
        if (!this._tableEditorHelper) {
            this._tableEditorHelper = new TableEditorHelper(this.page, this.editor);
        }
        return this._tableEditorHelper;
    }

    public get readModeCommentBubble(): ReadModeCommentBubbleHelper
    {
        if (!this._readModeCommentBubbleHelper) {
            this._readModeCommentBubbleHelper = new ReadModeCommentBubbleHelper(this.page);
        }
        return this._readModeCommentBubbleHelper;
    }

    public get imageDialog(): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators -- custom TipTap dialog, no ARIA role available
        return this.page.locator('.image-dialog');
    }

    public get videoDialog(): Locator
    {
        return this.page.getByRole('dialog', { name: 'Insert video' });
    }

    public get videoEmbedPlaceholders(): Locator
    {
        // VideoEmbedExtension nodeView wraps each placeholder in a role="figure"
        // labelled "<Provider> video" (or "Invalid video" for tampered nodes).
        return this.page.getByRole('figure', { name: /\bvideo$/i });
    }

    public get videoEmbedIframes(): Locator
    {
        // VideoEmbedRenderer emits iframes with title="<Provider> video player".
        return this.page.getByTitle(/video player$/i);
    }

    public get videoEmbedVideos(): Locator
    {
        // VideoEmbedRenderer emits direct <video> elements with title="Embedded video".
        return this.page.getByTitle('Embedded video');
    }

    public get subject(): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute used by ArticleController.js, not a test ID
        return this.page.locator('[data-glpi-kb-subject]');
    }

    public async goto(id: number): Promise<void>
    {
        await this.page.goto(
            `/front/knowbaseitem.form.php?id=${id}&forcetab=KnowbaseItem$1`,
            { waitUntil: 'domcontentloaded' }
        );
    }

    /**
     * The article header's dots menu trigger, scoped to avoid other "More
     * actions" menus (aside rows, comments); `.first()` picks the header's.
     */
    public get articleActionsMenu(): Locator
    {
        return this.page
            .getByTestId('kb-article')
            .getByRole('button', { name: 'More actions' })
            .first();
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

    public async doToggleFavoriteStatus(): Promise<void>
    {
        const favorite_toggle = this.getButton('Add to favorites');
        const response_promise = this.page.waitForResponse(
            response => response.url().includes('/ToggleFavorite')
        );
        await favorite_toggle.click();
        await response_promise;
    }

    public get favoritesSection(): Locator
    {
        return this.page.getByTestId('kb-aside-favorites');
    }

    public getFavoriteArticle(title: string): Locator
    {
        return this.favoritesSection.getByRole('link', { name: title });
    }

    public get aside(): Locator
    {
        return this.page.getByRole('main').getByRole('complementary');
    }

    public getAsideTitle(): Locator
    {
        return this.aside.getByRole('heading', { name: 'Articles' });
    }

    public getAsideCollapseButton(): Locator
    {
        return this.aside.getByRole('button', { name: 'Collapse articles list' });
    }

    public getAsideExpandButton(): Locator
    {
        return this.aside.getByRole('button', { name: 'Show articles list' });
    }

    public async doCollapseAside(): Promise<void>
    {
        await this.getAsideCollapseButton().click();
    }

    public async doExpandAside(): Promise<void>
    {
        await this.getAsideExpandButton().click();
    }

    public getAsideTreeArticleRow(id: number): Locator
    {
        return this.aside.locator(`[data-glpi-kb-aside-tree] [data-glpi-kb-article-id="${id}"]`);
    }

    public getFavoriteArticleRow(id: number): Locator
    {
        return this.favoritesSection.locator(`[data-glpi-kb-article-id="${id}"]`);
    }

    public getAsideTreeArticleLine(id: number): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators -- using scope
        return this.getAsideTreeArticleRow(id).locator(':scope > .article-line');
    }

    /**
     * The illustration slot (`<use>` element for a native icon) of an article
     * row in the aside tree.
     */
    public getAsideTreeArticleIllustration(id: number): Locator
    {
        return this.getAsideTreeArticleLine(id)
            .getByTestId('kb-illustration')
            .getByTestId('illustration-use')
        ;
    }

    /**
     * The illustration slot (`<use>` element for a native icon) of an article
     * row in the aside favorites section.
     */
    public getFavoriteArticleIllustration(id: number): Locator
    {
        return this.getFavoriteArticleRow(id)
            .getByTestId('kb-illustration')
            .getByTestId('illustration-use')
        ;
    }

    /**
     * The "create a child article" (+) trigger of an article in the aside tree.
     */
    public getAsideArticleAddChildTrigger(id: number): Locator
    {
        return this.getAsideTreeArticleLine(id).getByTitle('Create a child article');
    }

    /**
     * The dots menu trigger button of an article in the aside tree.
     */
    public getAsideArticleMenuTrigger(id: number): Locator
    {
        return this.getAsideTreeArticleLine(id).getByRole('button', { name: 'More actions' });
    }

    /**
     * Open the (lazy-loaded) dots menu of an article in the aside tree.
     */
    public async doOpenAsideArticleMenu(id: number): Promise<void>
    {
        await this.getAsideTreeArticleLine(id).hover();
        await this.getAsideArticleMenuTrigger(id).click();
        // The aside menu content is lazy-loaded; wait until it is rendered.
        await expect(this.getAsideArticleAction(id, 'Add to favorites')).toBeVisible();
    }

    /**
     * An action button inside an aside tree article's dots menu.
     */
    public getAsideArticleAction(id: number, name: string): Locator
    {
        return this.getAsideTreeArticleLine(id).getByRole('button', { name });
    }

    public async doToggleAsideFavorite(id: number): Promise<void>
    {
        const response_promise = this.page.waitForResponse(
            response => response.url().includes('/ToggleFavorite')
        );
        await this.getAsideArticleAction(id, 'Add to favorites').click();
        await response_promise;
    }

    public async doToggleAsideFaq(id: number): Promise<void>
    {
        const response_promise = this.page.waitForResponse(
            response => response.url().includes('/ToggleField')
        );
        await this.getAsideArticleAction(id, 'Add to FAQ').click();
        await response_promise;
    }

    public async doDeleteAsideArticle(id: number): Promise<void>
    {
        await this.getAsideArticleAction(id, 'Delete article').click();

        const confirm_button = this.page.getByRole('button', { name: 'Delete', exact: true });
        await expect(confirm_button).toBeVisible();

        const response_promise = this.page.waitForResponse(
            response => response.url().includes('/Delete')
        );
        await confirm_button.click();
        await response_promise;
    }

    /**
     * "Add to favorites" toggles currently visible in the aside, i.e. inside an
     * open dots menu. Used to assert that only a single menu is open at a time.
     */
    public get openAsideFavoriteToggles(): Locator
    {
        return this.aside
            .getByRole('button', { name: 'Add to favorites' })
            .filter({ visible: true });
    }

    public get childEntitiesCheckbox(): Locator
    {
        return this.page.getByRole('checkbox', { name: 'Child entities' });
    }

    public async doToggleChildEntities(): Promise<void>
    {
        // Wait for ArticleController to finish initialization (it removes pe-none after attaching all listeners)
        // eslint-disable-next-line playwright/no-raw-locators -- No semantic alternative for article container
        await this.page.locator('[data-glpi-knowbase-article]:not(.pe-none)').waitFor();
        await this.childEntitiesCheckbox.click();
    }

    public async doOpenCommentsPanel(): Promise<void>
    {
        await this.articleActionsMenu.click();
        await this.getButton('Comments').click();
    }

    /**
     * Wait for ArticleController's init, including highlight marks (same
     * `pe-none` readiness signal as doToggleChildEntities/waitForAsideReady).
     */
    public async waitForArticleReady(): Promise<void>
    {
        // eslint-disable-next-line playwright/no-raw-locators -- no semantic alternative for article container
        await this.page.locator('[data-glpi-knowbase-article]:not(.pe-none)').waitFor();
    }

    public getCommentByContent(content: string): Locator
    {
        return this.page.getByText(content).filter({
            'visible': true,
        });
    }

    public getCommentsCounter(): Locator
    {
        return this.page.getByTestId('comments-counter');
    }

    public getNoCommentsMessage(): Locator
    {
        return this.page.getByText('No comments yet.');
    }

    public getComment(content: string): Locator
    {
        return this.page.getByTestId('comment').filter({
            hasText: content
        });
    }

    /**
     * Select `text` in the read-only article content (outside edit mode) to
     * trigger the ReadModeSelectionBubble. Uses a scripted Range instead of a
     * triple-click, which doesn't reliably confine to one paragraph.
     */
    public async selectTextInReadMode(text: string): Promise<void>
    {
        await this.waitForArticleReady();
        await this.selectTextIn('[data-glpi-kb-content]', text, true);
    }

    /**
     * Select `text` inside the editor so the next keystroke replaces exactly that
     * run. Lets a test edit within a comment's quoted passage the way a user does,
     * instead of replacing the whole document through setContent().
     */
    public async selectTextInEditMode(text: string): Promise<void>
    {
        // eslint-disable-next-line playwright/no-raw-locators -- ProseMirror's own root, as TipTapEditorHelper uses
        const editor = this.page.locator('.ProseMirror[contenteditable="true"]');
        await editor.waitFor();
        await editor.focus();
        await this.selectTextIn('.ProseMirror[contenteditable="true"]', text, false);
    }

    /**
     * Scripted Range shared by the read-mode and edit-mode selection helpers.
     */
    private async selectTextIn(
        container_selector: string,
        text: string,
        dispatch_selectionchange: boolean,
    ): Promise<void>
    {
        await this.page.evaluate(({ selector, needle, dispatch }) => {
            const container = document.querySelector(selector);
            if (!container) {
                return;
            }
            const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
            let node = walker.nextNode();
            while (node) {
                const idx = (node.nodeValue ?? '').indexOf(needle);
                if (idx !== -1) {
                    const range = document.createRange();
                    range.setStart(node, idx);
                    range.setEnd(node, idx + needle.length);
                    const selection = window.getSelection();
                    if (!selection) {
                        return;
                    }
                    selection.removeAllRanges();
                    selection.addRange(range);
                    if (dispatch) {
                        document.dispatchEvent(new Event('selectionchange'));
                    }
                    return;
                }
                node = walker.nextNode();
            }
        }, { selector: container_selector, needle: text, dispatch: dispatch_selectionchange });
    }

    /**
     * Select the paragraph containing `text` the way a triple-click does, with
     * Range boundaries on the element instead of on a text node.
     */
    public async selectWholeParagraphInReadMode(text: string): Promise<void>
    {
        await this.waitForArticleReady();
        await this.page.evaluate((needle) => {
            const paragraph = [...document.querySelectorAll('[data-glpi-kb-content] p')]
                .find((candidate) => (candidate.textContent ?? '').includes(needle));
            const selection = window.getSelection();
            if (!paragraph || !selection) {
                return;
            }
            const range = document.createRange();
            range.selectNodeContents(paragraph);
            selection.removeAllRanges();
            selection.addRange(range);
            document.dispatchEvent(new Event('selectionchange'));
        }, text);
    }

    /**
     * All comment-anchor highlights currently rendered in the article content.
     */
    public getCommentHighlights(): Locator
    {
        return this.page.getByRole('article').getByRole('button', { name: 'View comment' });
    }

    public getCommentHighlightByText(text: string): Locator
    {
        return this.getCommentHighlights().filter({ hasText: text });
    }

    /**
     * Read as a computed style: filtering on a class would need a raw locator.
     */
    public async getCommentHighlightThickness(text: string): Promise<string>
    {
        return this.getCommentHighlightByText(text)
            .evaluate((el) => getComputedStyle(el).textDecorationThickness);
    }

    /**
     * Anchor quotes currently displayed on comment threads in the side panel.
     */
    public getCommentAnchorQuotes(): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute used by CommentsPanelController.js, not a test ID
        return this.page.locator('[data-glpi-comments]')
            .getByRole('blockquote')
            .filter({ visible: true });
    }

    public getPendingAnchorQuote(): Locator
    {
        return this.page.getByTestId('pending-anchor-quote').filter({ visible: true });
    }

    /**
     * Addressed by text because the comment id is not known to the test.
     */
    public getCommentThreadByContent(content: string): Locator
    {
        return this.page.getByTestId(/^comment-thread-/)
            .filter({ hasText: content })
            .filter({ visible: true });
    }

    public getNewCommentTextarea(): Locator
    {
        return this.page.getByPlaceholder("Add a comment...");
    }

    public getCommentsCountButton(): Locator
    {
        return this.page.getByTestId('comments-count');
    }

    public async doOpenCommentsPanelFromHeader(): Promise<void>
    {
        await this.getCommentsCountButton().click();
    }

    public async doSelectFilesForKbUpload(files: string[], modal: Locator): Promise<void>
    {
        const filePaths = files.map(file => path.join(__dirname, `../../fixtures/${file}`));

        // Use filechooser event - click the label to trigger the hidden file input
        const fileChooserPromise = this.page.waitForEvent('filechooser');
        await modal.getByText('Drop files here or click to browse').click();
        const fileChooser = await fileChooserPromise;
        await fileChooser.setFiles(filePaths);

        // Wait for files to be processed and appear in preview
        await expect(modal.getByRole('listitem')).toHaveCount(files.length);

        // Wait for all uploads to tmp to complete (button becomes enabled)
        await expect(modal.getByRole('button', { name: 'Upload Documents' })).toBeEnabled();
    }

    public async doAddFileToKbUploadArea(file: string, modal: Locator): Promise<void>
    {
        await this.doSelectFilesForKbUpload([file], modal);
        await modal.getByRole('button', { name: 'Upload Documents' }).click();
        await expect(modal).toBeHidden();
        // Wait for page reload after upload
        await this.page.waitForLoadState('load');
    }

    public async doAddFilesToKbUploadArea(files: string[], modal: Locator): Promise<void>
    {
        await this.doSelectFilesForKbUpload(files, modal);
        await modal.getByRole('button', { name: 'Upload Documents' }).click();
        await expect(modal).toBeHidden();
        // Wait for page reload after upload
        await this.page.waitForLoadState('load');
    }

    public async doEnableSchedulePanel(): Promise<void>
    {
        await this.articleActionsMenu.click();
        await this.getButton('Schedule visibility').click();
        await expect(this.page.getByTestId('schedule-panel')).toBeVisible();
    }

    public async doApplyVisibilityDates(): Promise<void>
    {
        // Save values
        await this.page.getByTestId('schedule-apply-btn').click();
        await expect(this.getAlert('Visibility dates updated')).toBeVisible();
    }

    public getVisibilityDatesIndicator(): Locator
    {
        return this.getLink("Scheduled");
    }

    public async doOpenVisibilityModal(): Promise<void>
    {
        await this.articleActionsMenu.click();
        await this.getButton('Permissions').click();
    }

    public getVisibilityModal(): Locator
    {
        return this.page.getByRole('dialog');
    }

    public getScheduledStartDateInput(): Locator
    {
        return this.page.getByPlaceholder('No start date').filter({visible: true});
    }

    public getScheduledEndDateInput(): Locator
    {
        return this.page.getByPlaceholder('No end date').filter({visible: true});
    }

    public async doOpenHistoryPanel(): Promise<void>
    {
        await this.articleActionsMenu.click();
        await this.getButton('History').click();
    }

    public getHistoryEvents(): Locator
    {
        return this.page.getByTestId('history-event').filter({visible: true});
    }

    public getHistoryEventByText(text: string): Locator
    {
        return this.getHistoryEvents().filter({hasText: text});
    }

    public getAsideCategory(title: string): Locator
    {
        return this.page.getByRole('group', { name: title });
    }

    public getAsideCategoryToggle(title: string): Locator
    {
        return this.page.getByRole('button', { name: title, exact: true });
    }

    public getAsideCategoryArticle(
        category_title: string,
        article_title: string
    ): Locator {
        return this.getAsideCategory(category_title).getByRole('link', {
            name: article_title
        });
    }

    public getAsideCategoryCreateInput(category_title: string): Locator
    {
        return this.getAsideCategory(category_title).getByPlaceholder('New article...');
    }

    /**
     * An article's own title link within its aside tree node (as opposed to a
     * child article's link, or the "+" add-child link). Every node (leaf or
     * not, as long as article creation is allowed) has exactly one of these,
     * so it is a safe hover/click target regardless of whether the article
     * has a fold toggle (which only renders when it already has children).
     */
    public getAsideArticleTitleLink(title: string): Locator
    {
        return this.getAsideCategory(title).getByRole('link', { name: title, exact: true });
    }

    /**
     * The "+" link that creates a child article under the given (parent)
     * article's aside tree node. Hidden until the node's header is hovered
     * or focused (see `_kb.scss`), hence exposed separately from
     * `getAsideCategoryArticle()`.
     */
    public getAsideCategoryAddLink(title: string): Locator
    {
        return this.getAsideCategory(title).getByRole('link', {
            name: new RegExp(`Create an article under ${title}`, 'i'),
        });
    }

    public async doToggleAsideCategory(title: string): Promise<void>
    {
        await this.getAsideCategoryToggle(title).click();
    }

    /**
     * Toggle an article and wait for its fold state to be persisted server-side.
     * Persistence is a fire-and-forget POST, so callers that reload right after
     * toggling must wait for it, otherwise the reload can abort the in-flight
     * request and the server renders stale state.
     */
    public async doToggleAsideCategoryAndWaitForPersist(title: string): Promise<void>
    {
        await Promise.all([
            this.page.waitForResponse(
                (response) =>
                    /\/Knowbase\/Aside\/Article\/\d+\/Fold$/.test(response.url())
                    && response.request().method() === 'POST'
                    && response.ok()
            ),
            this.doToggleAsideCategory(title),
        ]);
    }

    /**
     * Wait until the aside controller has finished initializing. It signals
     * readiness by removing the `pe-none` class from the search input once all
     * listeners (search, category toggle, actions) are attached, so interacting
     * before this can silently no-op.
     */
    public async waitForAsideReady(): Promise<void>
    {
        await expect(this.asideSearchInput).not.toHaveClass(/pe-none/);
    }

    /**
     * The root-level container of the aside article tree.
     */
    public get asideTree(): Locator
    {
        return this.page.getByTestId('aside-tree');
    }

    /**
     * The root tree's own header row, hovered/focused to reveal
     * `asideRootCreateLink` (mirrors a regular node's header).
     */
    public get asideRootHeader(): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators -- using scope
        return this.asideTree.locator(':scope > [data-glpi-kb-aside-category-header]');
    }

    public get asideSearchInput(): Locator
    {
        return this.page.getByLabel('Search articles');
    }

    public get asideNoResultsMessage(): Locator
    {
        return this.page.getByText("No articles found.");
    }

    public async doSearchAside(term: string): Promise<void>
    {
        // Click on the input to make sure the js script is ready, before that
        // it won't be clickable.
        await this.asideSearchInput.click();
        await this.asideSearchInput.fill(term);
    }

    public get asideSearchClearButton(): Locator
    {
        return this.page.getByRole('main')
            .getByRole('complementary')
            .getByLabel('Clear search')
        ;
    }

    public async doClearAsideSearch(): Promise<void>
    {
        await this.asideSearchInput.clear();
    }

    public async doClickAsideSearchClear(): Promise<void>
    {
        await this.asideSearchClearButton.click();
    }

    /**
     * Open the header "Share" popover and wait for its lazily-loaded content
     * to be ready.
     */
    public async openSharePopover(): Promise<void>
    {
        await this.getButton('Share').click();
        await expect(this.publishSwitch()).toBeVisible();
    }

    public publishSwitch(): Locator
    {
        return this.page.getByRole('switch', { name: 'Publish to web' });
    }

    public shareLink(): Locator
    {
        return this.page.getByLabel('Public link');
    }

    public copyLinkButton(): Locator
    {
        return this.page.getByRole('button', { name: 'Copy link' });
    }

    /**
     * Read the full share URL (with secret) via the copy button. The field can be
     * visually truncated, so the copy affordance is the reliable way to get the
     * whole link. Requires the clipboard permissions on the browser context.
     */
    public async copiedShareUrl(): Promise<string>
    {
        // Clear first so we never read a stale value from a previous copy (the
        // copy handler writes asynchronously, e.g. right after a regenerate).
        await this.page.evaluate(() => navigator.clipboard.writeText(''));
        await this.copyLinkButton().click();
        await expect
            .poll(() => this.page.evaluate(() => navigator.clipboard.readText()))
            .toContain('/Share/');
        return this.page.evaluate(() => navigator.clipboard.readText());
    }

    public regenerateButton(): Locator
    {
        return this.page.getByRole('button', { name: 'Regenerate link' });
    }

    /**
     * Confirm the "Regenerate link" danger dialog opened by regenerateButton().
     */
    public async confirmRegenerate(): Promise<void>
    {
        await this.page.getByRole('button', { name: 'Regenerate', exact: true }).click();
    }
}

