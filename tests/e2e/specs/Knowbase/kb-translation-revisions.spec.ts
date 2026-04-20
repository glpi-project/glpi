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

test('Translation revisions appear in history panel', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create an article with a translation and update it once.
    const kb_id = await api.knowbase.createArticle();
    const translation_id = await api.knowbase.addTranslation(kb_id, "fr_FR", {
        name    : 'Mon article',
        answer  : 'Mon contenu',
    });
    await api.knowbase.updateTranslation(translation_id, {
        name: 'Mon article v2',
        answer: 'Mon contenu v2',
    });

    // Act: go to article and open the history
    await kb.goto(kb_id);
    await kb.doOpenHistoryPanel();

    // Assert: the current version of the translation and its first revision
    // should be visible.
    await expect.soft(kb.getHistoryEventByText('Français — Version 1')).toBeVisible();
    await expect(kb.getHistoryEventByText('Français — Current version')).toBeVisible();
});

test('Can view the current version of a language', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create an article with a translation and update it once.
    const kb_id = await api.knowbase.createArticle();
    const translation_id = await api.knowbase.addTranslation(kb_id, "fr_FR", {
        name    : 'Mon article',
        answer  : 'Mon contenu',
    });
    await api.knowbase.updateTranslation(translation_id, {
        name: 'Mon article v2',
        answer: 'Mon contenu v2',
    });

    // Act: go to article, open the history and click on the current version
    // for the french language
    await kb.goto(kb_id);
    await kb.doOpenHistoryPanel();
    await kb.getHistoryEventByText('Français — Current version').click();

    // Assert: the content from this translation should be loaded on the screen
    const translation_mode_alert = page.getByTestId('translation-mode-alert');
    await expect.soft(translation_mode_alert).toBeVisible();
    await expect.soft(page.getByTestId('subject')).toHaveText('Mon article v2');
    await expect.soft(page.getByTestId('content')).toHaveText('Mon contenu v2');
    await expect.soft(kb.getHistoryEventByText('Français — Current version'))
        .toHaveClass(/kb-revision--selected/)
    ;

    // Controls should be disabled as this is not the real translation mode,
    // only a preview.
    await expect.soft(translation_mode_alert.getByTestId('translation-save-btn'))
        .toBeHidden()
    ;
    await expect.soft(translation_mode_alert.getByTestId('translation-delete-btn'))
        .toBeHidden()
    ;
});

test('Can view a revision for a language', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create an article with a translation and update it once.
    const kb_id = await api.knowbase.createArticle();
    const translation_id = await api.knowbase.addTranslation(kb_id, "fr_FR", {
        name    : 'Mon article',
        answer  : 'Mon contenu',
    });
    await api.knowbase.updateTranslation(translation_id, {
        name: 'Mon article v2',
        answer: 'Mon contenu v2',
    });

    // Act: go to article, open the history and click on the first version
    // for the french language
    await kb.goto(kb_id);
    await kb.doOpenHistoryPanel();
    await kb.getHistoryEventByText('Français — Version 1').click();

    // Assert: the content from this translation should be compared.
    // Since "v2" is the only new content, it should be marked as inserted text.
    const translation_mode_alert = page.getByTestId('translation-mode-alert');
    await expect.soft(translation_mode_alert).toBeVisible();
    await expect.soft(page.getByTestId('content').getByRole('insertion')).toHaveText('v2');
    await expect.soft(kb.getHistoryEventByText('Français — Version 1'))
        .toHaveClass(/kb-revision--selected/)
    ;

    // Controls should be disabled as this is not the real translation mode,
    // only a preview.
    await expect.soft(translation_mode_alert.getByTestId('translation-save-btn'))
        .toBeHidden()
    ;
    await expect.soft(translation_mode_alert.getByTestId('translation-delete-btn'))
        .toBeHidden()
    ;
});

test('Can revert a translation revision', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create an article with a translation and update it once.
    const kb_id = await api.knowbase.createArticle();
    const translation_id = await api.knowbase.addTranslation(kb_id, "fr_FR", {
        name    : 'Mon article',
        answer  : 'Mon contenu',
    });
    await api.knowbase.updateTranslation(translation_id, {
        name: 'Mon article v2',
        answer: 'Mon contenu v2',
    });

    // Act: go to article, open the history and click on the current version
    // for the french language, then restore it.
    await kb.goto(kb_id);
    await kb.doOpenHistoryPanel();
    const log = kb.getHistoryEventByText('Français — Version 1');
    await log.getByTitle('Restore this translation version').click();

    // Confirm the action.
    const dialog = kb.getDialog('Restore translation revision');
    await dialog.getByRole('button', { name: 'Confirm' }).click();

    // Reopen the history to view the active translation for french.
    await kb.doOpenHistoryPanel();
    await kb.getHistoryEventByText('Français — Current version').click();

    // Assert: article should be reloaded with its original content for the
    // french language.
    await expect.soft(page.getByTestId('translation-mode-alert')).toBeVisible();
    await expect.soft(page.getByTestId('subject')).toHaveText('Mon article');
    await expect(page.getByTestId('content')).toHaveText('Mon contenu');
});

test('Current version preview is blocked when in edit mode', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create an article with a translation and update it once.
    const kb_id = await api.knowbase.createArticle();
    const translation_id = await api.knowbase.addTranslation(kb_id, "fr_FR", {
        name    : 'Mon article',
        answer  : 'Mon contenu',
    });
    await api.knowbase.updateTranslation(translation_id, {
        name: 'Mon article v2',
        answer: 'Mon contenu v2',
    });

    // Act: go to article and trigger the edit mode, then try to view a revision
    await kb.goto(kb_id);
    await page.getByTestId('edit-button').click();
    await kb.doOpenHistoryPanel();
    await kb.getHistoryEventByText('Français — Current version').click();

    // Assert: a dialog should be shown
    await expect.soft(kb.getDialog('Action unavailable')).toBeVisible();
    await expect(kb.getHistoryEventByText('Français — Version 1'))
        .not
        .toHaveClass(/kb-revision--selected/)
    ;
});

test('Diff preview is blocked when in edit mode', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create an article with a translation and update it once.
    const kb_id = await api.knowbase.createArticle();
    const translation_id = await api.knowbase.addTranslation(kb_id, "fr_FR", {
        name    : 'Mon article',
        answer  : 'Mon contenu',
    });
    await api.knowbase.updateTranslation(translation_id, {
        name: 'Mon article v2',
        answer: 'Mon contenu v2',
    });

    // Act: go to article and trigger the edit mode, then try to view a revision
    await kb.goto(kb_id);
    await page.getByTestId('edit-button').click();
    await kb.doOpenHistoryPanel();
    await kb.getHistoryEventByText('Français — Version 1').click();

    // Assert: a dialog should be shown
    await expect.soft(kb.getDialog('Preview unavailable')).toBeVisible();
    await expect(kb.getHistoryEventByText('Français — Version 1'))
        .not
        .toHaveClass(/kb-revision--selected/)
    ;
});

test('Current version preview is blocked when in translation mode', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create an article with a translation and update it once.
    const kb_id = await api.knowbase.createArticle();
    const translation_id = await api.knowbase.addTranslation(kb_id, "fr_FR", {
        name    : 'Mon article',
        answer  : 'Mon contenu',
    });
    await api.knowbase.updateTranslation(translation_id, {
        name: 'Mon article v2',
        answer: 'Mon contenu v2',
    });

    // Act: go to article and trigger the edit mode, then try to view a revision
    await kb.goto(kb_id);
    await page.getByTestId('translations-count').click();
    await kb.doOpenHistoryPanel();
    await kb.getHistoryEventByText('Français — Current version').click();

    // Assert: a dialog should be shown
    await expect.soft(kb.getDialog('Action unavailable')).toBeVisible();
    await expect(kb.getHistoryEventByText('Français — Version 1'))
        .not
        .toHaveClass(/kb-revision--selected/)
    ;
});

test('Diff preview is blocked when in translation mode', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create an article with a translation and update it once.
    const kb_id = await api.knowbase.createArticle();
    const translation_id = await api.knowbase.addTranslation(kb_id, "fr_FR", {
        name    : 'Mon article',
        answer  : 'Mon contenu',
    });
    await api.knowbase.updateTranslation(translation_id, {
        name: 'Mon article v2',
        answer: 'Mon contenu v2',
    });

    // Act: go to article and trigger the edit mode, then try to view a revision
    await kb.goto(kb_id);
    await page.getByTestId('translations-count').click();
    await kb.doOpenHistoryPanel();
    await kb.getHistoryEventByText('Français — Version 1').click();

    // Assert: a dialog should be shown
    await expect.soft(kb.getDialog('Preview unavailable')).toBeVisible();
    await expect(kb.getHistoryEventByText('Français — Version 1'))
        .not
        .toHaveClass(/kb-revision--selected/)
    ;
});
