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

test.describe('Knowledge Base Editor - Link Dialog', () => {

    test('Creates a same-tab link when the new-tab box is left unchecked', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog same tab',
            entities_id: getWorkerEntityId(),
            answer: '<p>Link text</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.clickButton('Link');

        const dialog = kb.linkDialog;
        await expect(dialog).toBeVisible();
        await expect(dialog.getByLabel('Open in new tab')).not.toBeChecked();

        await dialog.getByLabel('URL', { exact: true }).fill('https://example.com');
        await dialog.getByRole('button', { name: 'Save' }).click();
        await expect(dialog).toBeHidden();

        await kb.editor.save();

        await kb.editor.assertHasLink('Link text', 'https://example.com');
        await kb.editor.assertLinkOpensInSameTab('Link text');
    });

    test('Creates a new-tab link when the box is checked', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog new tab',
            entities_id: getWorkerEntityId(),
            answer: '<p>Link text</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.setLink('https://example.com', true);
        await kb.editor.save();

        await kb.editor.assertHasLink('Link text', 'https://example.com');
        await kb.editor.assertLinkOpensInNewTab('Link text');
    });

    // Regression: extension-link seeds the `target` mark attribute default from
    // options.HTMLAttributes.target. With the library default of '_blank', a
    // stored link with no target is re-parsed as _blank on reopen, so an
    // unchecked box silently reverts on the next save.
    test('A same-tab link stays same-tab after an edit round trip', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog round trip',
            entities_id: getWorkerEntityId(),
            answer: '<p><a href="https://example.com">Linked text</a></p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.save();

        await kb.editor.assertHasLink('Linked text', 'https://example.com');
        await kb.editor.assertLinkOpensInSameTab('Linked text');
    });

    test('The dialog prefills URL and new-tab state from the existing link', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog prefill',
            entities_id: getWorkerEntityId(),
            answer: '<p><a href="https://example.com" target="_blank">Linked text</a></p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.clickButton('Link');

        const dialog = kb.linkDialog;
        await expect(dialog.getByLabel('URL', { exact: true })).toHaveValue('https://example.com');
        await expect(dialog.getByLabel('Open in new tab')).toBeChecked();
    });

    test('Clearing the URL removes the link', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog clear',
            entities_id: getWorkerEntityId(),
            answer: '<p><a href="https://example.com">Linked text</a></p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.clickButton('Link');

        const dialog = kb.linkDialog;
        await dialog.getByLabel('URL', { exact: true }).fill('');
        await dialog.getByRole('button', { name: 'Save' }).click();
        await expect(dialog).toBeHidden();

        await kb.editor.save();

        await kb.editor.assertContainsText('Linked text');
        await expect(kb.editor.contentContainer.getByRole('link')).toHaveCount(0);
    });

    // Regression: setLink() returns false on a scheme outside the extension's
    // allowlist. The dialog used to close regardless, leaving the text unlinked
    // with no feedback.
    test('A rejected URL scheme keeps the dialog open and reports the error', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog rejected scheme',
            entities_id: getWorkerEntityId(),
            answer: '<p>Link text</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.clickButton('Link');

        const dialog = kb.linkDialog;
        await dialog.getByLabel('URL', { exact: true }).fill('javascript:alert(1)');
        await dialog.getByRole('button', { name: 'Save' }).click();

        await expect(dialog).toBeVisible();
        await expect(dialog.getByRole('alert')).toBeVisible();
        await expect(dialog.getByLabel('URL', { exact: true })).toHaveAttribute('aria-invalid', 'true');
        await expect(kb.editor.contentContainer.getByRole('link')).toHaveCount(0);
    });

    test('A relative path is kept relative', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog relative path',
            entities_id: getWorkerEntityId(),
            answer: '<p>Link text</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.setLink('docs/faq.html');
        await kb.editor.save();

        await kb.editor.assertHasLink('Link text', 'docs/faq.html');
    });

    // Regression: a host:port URL read as an unsupported scheme and was refused.
    test('A host:port URL is saved as https', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog host port',
            entities_id: getWorkerEntityId(),
            answer: '<p>Link text</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.setLink('intranet:8080/admin');
        await kb.editor.save();

        await kb.editor.assertHasLink('Link text', 'https://intranet:8080/admin');
    });

    test('Unchecking the box turns an existing new-tab link back to same-tab', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog back to same tab',
            entities_id: getWorkerEntityId(),
            answer: '<p><a href="https://example.com" target="_blank">Linked text</a></p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.setLink('https://example.com', false);
        await kb.editor.save();

        await kb.editor.assertLinkOpensInSameTab('Linked text');
    });

    // The tests below cover the shared dialog shell (EditorDialog.js); the link
    // dialog is just the cheapest way in.

    // Regression: clicking a non-focusable part of the dialog blurs to
    // document.body, which used to match neither end of the focusable range.
    // A forward Tab still lands in the dialog on its own, since the click sets
    // the sequential focus navigation starting point to the header title, but
    // Shift+Tab walks backwards from there and used to leave the overlay.
    test('Shift+Tab stays inside the dialog after clicking a non-focusable area', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog focus trap',
            entities_id: getWorkerEntityId(),
            answer: '<p>Link text</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.clickButton('Link');

        const dialog = kb.linkDialog;
        await dialog.getByText('Insert/Edit link').click();
        await page.keyboard.press('Shift+Tab');

        await expect(dialog.getByRole('button', { name: 'Close' })).toBeFocused();
    });

    // Regression: a drag's click lands on the nearest common ancestor of the
    // press and the release, so releasing over the backdrop read as a
    // click-outside and dismissed the dialog with the typed URL in it.
    test('Releasing a drag over the backdrop keeps the dialog open', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog drag release',
            entities_id: getWorkerEntityId(),
            answer: '<p>Link text</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.clickButton('Link');

        const dialog = kb.linkDialog;
        const url_input = dialog.getByLabel('URL', { exact: true });
        await url_input.fill('https://example.com/keep-me');

        // Drag-select the field, then release outside the dialog.
        await url_input.hover();
        await page.mouse.down();
        await page.mouse.move(5, 5, { steps: 10 });
        await page.mouse.up();

        await expect(dialog).toBeVisible();
        await expect(url_input).toHaveValue('https://example.com/keep-me');
    });

    // Regression: the click-outside guard only tracked where the press started,
    // so a press on the backdrop released inside the dialog dismissed it too.
    test('Releasing a drag inside the dialog keeps it open', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog drag inward',
            entities_id: getWorkerEntityId(),
            answer: '<p>Link text</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.clickButton('Link');

        const dialog = kb.linkDialog;
        const url_input = dialog.getByLabel('URL', { exact: true });
        await url_input.fill('https://example.com/keep-me');

        // Press on the backdrop, release over the dialog header.
        await page.mouse.move(5, 5);
        await page.mouse.down();
        await dialog.getByText('Insert/Edit link').hover();
        await page.mouse.up();

        await expect(dialog).toBeVisible();
        await expect(url_input).toHaveValue('https://example.com/keep-me');
    });

    // Regression: a selection carrying the code mark can't take a link mark, so
    // the dialog reported a scheme error no URL could satisfy.
    test('The Link button is disabled on a selection that cannot carry a link', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog code selection',
            entities_id: getWorkerEntityId(),
            answer: '<p>Link text</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.clickButton('Code');

        await kb.bubbleMenu.assertButtonDisabled('Link');
    });

    // Regression: tiptap reads a scheme-less URL as relative, so "example.com"
    // was stored verbatim and resolved against the article URL.
    test('A scheme-less URL is saved as https', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog scheme-less URL',
            entities_id: getWorkerEntityId(),
            answer: '<p>Link text</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.setLink('example.com');
        await kb.editor.save();

        await kb.editor.assertHasLink('Link text', 'https://example.com');
    });

    test('Enter saves from the new-tab checkbox', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Link dialog enter from checkbox',
            entities_id: getWorkerEntityId(),
            answer: '<p>Link text</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.bubbleMenu.selectAllContent();
        await kb.bubbleMenu.clickButton('Link');

        const dialog = kb.linkDialog;
        await dialog.getByLabel('URL', { exact: true }).fill('https://example.com');
        await dialog.getByLabel('Open in new tab').focus();
        await page.keyboard.press('Enter');

        await expect(dialog).toBeHidden();
        await kb.editor.save();

        await kb.editor.assertHasLink('Link text', 'https://example.com');
    });
});
