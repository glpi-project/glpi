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

// The full share URL (with secret) is read from the clipboard via the copy button.
test.use({ permissions: ['clipboard-read', 'clipboard-write'] });

test('Publishing reuses the same link across unpublish/republish', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: `KB sharing publish - ${crypto.randomUUID()}`,
        entities_id: getWorkerEntityId(),
        answer: "Test content for publish",
    });

    await kb.goto(id);
    await expect(page.getByText('Test content for publish')).toBeVisible();
    await kb.openSharePopover();

    await kb.publishSwitch().check();
    await expect(kb.shareLink()).toBeVisible();
    const url1 = await kb.copiedShareUrl();
    expect(url1).toContain('/Share/');

    await kb.publishSwitch().uncheck();
    await expect(kb.shareLink()).not.toBeAttached();

    await kb.publishSwitch().check();
    await expect(kb.shareLink()).toBeVisible();
    // Republish reuses the same token → same full link.
    expect(await kb.copiedShareUrl()).toBe(url1);
});

test('Shared link is accessible by anonymous user', async ({ page, anonymousPage, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const article_name = `KB shared article - ${crypto.randomUUID()}`;
    const article_content = `Shared content - ${crypto.randomUUID()}`;
    const id = await api.createItem('KnowbaseItem', {
        name: article_name,
        entities_id: getWorkerEntityId(),
        answer: `<p>${article_content}</p>`,
    });

    await kb.goto(id);
    await expect(page.getByText(article_content)).toBeVisible();
    await kb.openSharePopover();

    await kb.publishSwitch().check();
    await expect(kb.shareLink()).toBeVisible();
    const share_url = await kb.copiedShareUrl();
    expect(share_url).toContain('/Share/');

    // Use path only so the test is agnostic of the server base URL (host/port vary between local and CI)
    const share_path = new URL(share_url, 'http://placeholder').pathname;
    await anonymousPage.goto(share_path);

    await expect(anonymousPage.getByText(article_name)).toBeVisible();
    await expect(anonymousPage.getByText(article_content)).toBeVisible();
});

test('Regenerating the link revokes the old one and issues a new one', async ({ page, anonymousPage, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const article_name = `KB regenerate article - ${crypto.randomUUID()}`;
    const article_content = `Regenerate content - ${crypto.randomUUID()}`;
    const id = await api.createItem('KnowbaseItem', {
        name: article_name,
        entities_id: getWorkerEntityId(),
        answer: `<p>${article_content}</p>`,
    });

    await kb.goto(id);
    await expect(page.getByText(article_content)).toBeVisible();
    await kb.openSharePopover();

    await kb.publishSwitch().check();
    await expect(kb.shareLink()).toBeVisible();
    const url1 = await kb.copiedShareUrl();
    const path1 = new URL(url1, 'http://placeholder').pathname;

    await kb.regenerateButton().click();
    await kb.confirmRegenerate();

    await expect(kb.shareLink()).toBeVisible();
    const url2 = await kb.copiedShareUrl();
    expect(url2).not.toBe(url1);
    const path2 = new URL(url2, 'http://placeholder').pathname;

    // Old link is revoked: the controller throws NotFoundHttpException for
    // any token that isn't found active in the database.
    const old_link_response = await anonymousPage.goto(path1);
    expect(old_link_response?.status()).toBe(404);

    // New link works.
    await anonymousPage.goto(path2);
    await expect(anonymousPage.getByText(article_name)).toBeVisible();
    await expect(anonymousPage.getByText(article_content)).toBeVisible();
});

test('Invalid token formats are rejected before reaching the controller', async ({ anonymousPage }) => {
    const invalid_paths = [
        '/Share/abc',
        '/Share/GGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGG',
    ];

    for (const path of invalid_paths) {
        const response = await anonymousPage.goto(path);
        expect(response?.status(), `Expected 404 for ${path}`).toBe(404);
    }
});
