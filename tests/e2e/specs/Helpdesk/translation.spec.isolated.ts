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

import { randomUUID } from 'crypto';
import type { Page } from '@playwright/test';
import { expect, test } from '../../fixtures/glpi_fixture';
import { HelpdeskTranslationPage, LanguageColumn } from '../../pages/HelpdeskTranslationPage';
import { LoginPage } from '../../pages/LoginPage';
import { Profiles } from '../../utils/Profiles';
import type { Api } from '../../utils/Api';
import { getWorkerEntityId, getWorkerLogin, getWorkerUserId } from '../../utils/WorkerEntities';

const TILE_ITEMTYPE = 'Glpi\\Helpdesk\\Tile\\GlpiPageTile';

const FRENCH = 'Français';
const GERMAN = 'Deutsch';

/**
 * Open the helpdesk home in a session of its own, with the given language.
 *
 * The language of a session is read when it is created, thus a new session is
 * the only way to see the helpdesk home in another language. Using a separate
 * browser context also keeps the session of the worker untouched.
 */
async function openHelpdeskHomeWithLanguage(
    anonymous_page: Page,
    api: Api,
    language: string,
): Promise<void> {
    await api.updateItem('User', getWorkerUserId(), { 'language': language });

    const login_page = new LoginPage(anonymous_page);
    await login_page.goto();
    await login_page.doLogin(getWorkerLogin(), getWorkerLogin());
    await anonymous_page.goto('/');

    // The whole interface is now in the language of the user, thus the profile
    // can't be changed through the menu: its labels are translated. The request
    // context of the page shares its cookies with the browser, thus it acts on
    // the same session.
    const csrf_token = await anonymous_page.evaluate(
        () => document
            .querySelector('meta[property="glpi:csrf_token"]')
            ?.getAttribute('content') ?? ''
    );
    const response = await anonymous_page.request.post('/Session/ChangeProfile', {
        form: { id: Profiles.SelfService },
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-Glpi-Csrf-Token': csrf_token,
        },
    });
    expect(response.ok()).toBe(true);

    await anonymous_page.goto('/Helpdesk');
}

test.describe('Helpdesk translations', () => {
    let tile_id: number;
    let tile_title: string;
    let tile_description: string;

    test.beforeEach(async ({ page, api, profile }) => {
        await profile.set(Profiles.SuperAdmin);

        const uuid = randomUUID();
        // The two values must not contain each other: rows are matched on
        // their text, and that match is not case sensitive.
        tile_title = `Tile title ${uuid}`;
        tile_description = `Tile description ${uuid}`;

        tile_id = await api.createItem(TILE_ITEMTYPE, {
            'title': tile_title,
            'description': tile_description,
            'page': 'faq',
            '_itemtype_item': 'Entity',
            '_items_id_item': getWorkerEntityId(),
        });

        // The translations are global, thus a run that was interrupted could
        // have left some. Start from a known state.
        await new HelpdeskTranslationPage(page).deleteAllTranslations();
    });

    // The translations are global to the whole application, and the language of
    // the worker user is shared by every test of this worker, thus both must be
    // restored whatever happened during the test.
    test.afterEach(async ({ page, api, profile }) => {
        await profile.set(Profiles.SuperAdmin);
        await api.updateItem('User', getWorkerUserId(), { 'language': '' });

        const translations = new HelpdeskTranslationPage(page);
        await translations.deleteAllTranslations();

        await api.purgeItem(TILE_ITEMTYPE, tile_id);
    });

    /**
     * Translate the title and the description of the test tile into French.
     */
    async function addFrenchTranslations(
        translations: HelpdeskTranslationPage,
    ): Promise<void> {
        await translations.addLanguage(FRENCH);

        const title_row = translations.getTranslationRow(FRENCH, tile_title);
        await expect(title_row).toContainText('Title');
        await translations.getTranslationInput(title_row)
            .fill(`${tile_title} in French`)
        ;

        const description_row = translations.getTranslationRow(FRENCH, tile_description);
        await expect(description_row).toContainText('Description');
        const rich_text = await translations.getTranslationRichText(description_row);
        await rich_text.fill(`${tile_description} in French`);

        await translations.saveTranslation(FRENCH);
    }

    test('can add a new language translation', async ({ page }) => {
        const translations = new HelpdeskTranslationPage(page);
        await translations.goto();
        await translations.addLanguage(FRENCH);

        // The modal lists the values that can be translated.
        const modal = translations.getModal(FRENCH);
        await expect(modal.getByRole('cell', { name: 'Translation name' }).first()).toBeVisible();
        await expect(modal.getByRole('cell', { name: 'Default value' }).first()).toBeVisible();
        await expect(modal.getByRole('cell', { name: 'Translated value' }).first()).toBeVisible();

        await translations.closeModal(FRENCH);

        // Nothing is translated yet.
        await expect(
            translations.getLanguageCell(FRENCH, LanguageColumn.Language)
        ).toContainText(FRENCH);
        await expect(
            translations.getLanguageCell(FRENCH, LanguageColumn.Translated)
        ).toContainText('0 %');
        await expect(
            translations.getLanguageCell(FRENCH, LanguageColumn.TranslationsToDo)
        ).not.toHaveText('0');
        await expect(
            translations.getLanguageCell(FRENCH, LanguageColumn.ObsoleteTranslations)
        ).toHaveText('0');
    });

    test('can add new translations', async ({ page }) => {
        const translations = new HelpdeskTranslationPage(page);
        await translations.goto();
        await addFrenchTranslations(translations);

        // The saved values must be shown again when the modal is opened.
        await translations.openLanguage(FRENCH);
        await expect(
            translations.getTranslationInput(
                translations.getTranslationRow(FRENCH, tile_title)
            )
        ).toHaveValue(`${tile_title} in French`);
    });

    test('translation stats are updated', async ({ page }) => {
        const translations = new HelpdeskTranslationPage(page);
        await translations.goto();
        await addFrenchTranslations(translations);
        await translations.goto();

        await expect(
            translations.getLanguageCell(FRENCH, LanguageColumn.Translated)
        ).not.toContainText('0 %');
        await expect(
            translations.getLanguageCell(FRENCH, LanguageColumn.ObsoleteTranslations)
        ).toHaveText('0');
    });

    test('can delete a helpdesk translation', async ({ page }) => {
        const translations = new HelpdeskTranslationPage(page);
        await translations.goto();
        await addFrenchTranslations(translations);

        await translations.openLanguage(FRENCH);
        await translations.deleteTranslation(FRENCH);

        await expect(translations.getLanguageRow(FRENCH)).toBeHidden();
    });

    test('a changed default value marks a translation as obsolete', async ({ page, api }) => {
        const translations = new HelpdeskTranslationPage(page);
        await translations.goto();
        await addFrenchTranslations(translations);

        // A second language, which has no translation at all.
        await translations.addLanguage(GERMAN);
        await translations.closeModal(GERMAN);

        // Change a default value that was translated into French only.
        await api.updateItem(TILE_ITEMTYPE, tile_id, {
            'title': `${tile_title} (changed)`,
        });
        await translations.goto();

        await expect(
            translations.getLanguageCell(FRENCH, LanguageColumn.ObsoleteTranslations)
        ).toHaveText('1');
        await expect(
            translations.getLanguageCell(GERMAN, LanguageColumn.ObsoleteTranslations)
        ).toHaveText('0');

        // The French entry of the changed value must carry the warning.
        await translations.openLanguage(FRENCH);
        await expect(
            translations
                .getTranslationRow(FRENCH, `${tile_title} (changed)`)
                .getByLabel('Translation may be obsolete')
        ).toBeVisible();
    });

    test('the helpdesk home uses the default value when the language is not translated', async ({ page, profile }) => {
        const translations = new HelpdeskTranslationPage(page);
        await translations.goto();
        await addFrenchTranslations(translations);

        // The session uses the default language of the application.
        await profile.set(Profiles.SelfService);
        await page.goto('/');
        await expect(page.getByTestId('quick-access')).toContainText(tile_title);
    });

    test('the helpdesk home uses the translation of the language of the user', async ({ page, anonymousPage, api }) => {
        const translations = new HelpdeskTranslationPage(page);
        await translations.goto();
        await addFrenchTranslations(translations);

        await openHelpdeskHomeWithLanguage(anonymousPage, api, 'fr_FR');
        await expect(
            anonymousPage.getByTestId('quick-access')
        ).toContainText(`${tile_title} in French`);
    });

    test('the helpdesk home falls back to the default value for another language', async ({ page, anonymousPage, api }) => {
        const translations = new HelpdeskTranslationPage(page);
        await translations.goto();
        await addFrenchTranslations(translations);

        // Spanish has no translation, thus the default values must be used.
        await openHelpdeskHomeWithLanguage(anonymousPage, api, 'es_ES');
        await expect(
            anonymousPage.getByTestId('quick-access')
        ).toContainText(tile_title);
    });
});
