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
import { expect, test } from '../../fixtures/glpi_fixture';
import { GlpiPage } from '../../pages/GlpiPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe('Entity', () => {
    test.afterEach(async ({ entity }) => {
        // Make sure we go back to the expected entity for this worker
        await entity.resetToDefaultWorkerEntity();
    });

    test('Should be able to create a sub-subentity in a sub-entity context', async ({
        page,
        profile,
        entity,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const subentity_name = `Subentity ${randomUUID()}`;

        // Start from the worker entity, with recursion, so the creation below
        // is allowed even if a previous run left sub entities behind.
        await entity.switchToWithRecursion(getWorkerEntityId());

        await page.goto('/front/entity.form.php');
        await page.getByLabel('Name').fill(subentity_name);
        await glpi_page.getButton("Add").click();

        // GLPI redirects to the form of the newly created entity, which gives
        // us its id
        await page.waitForURL(/\/front\/entity\.form\.php\?id=\d+/);
        const subentity_id = Number(
            new URL(page.url()).searchParams.get('id')
        );
        await entity.switchToWithoutRecursion(subentity_id);

        // We can create the sub-subentity (first child so recursive will be automatically set)
        await page.goto('/front/entity.form.php');
        let form_sent = page.waitForResponse((response) =>
            response.url().includes('/front/entity.form.php')
            && response.request().method() === 'POST'
        );
        await page.getByLabel('Name').fill(`First-sub-${subentity_name}`);
        await glpi_page.getButton("Add").click();
        expect((await form_sent).status()).toEqual(302);

        // We can't create the sub-subentity, form is inaccessible as we already have a sub-subentity
        await entity.switchToWithoutRecursion(subentity_id);

        const form_response = await page.goto('/front/entity.form.php');
        expect(form_response?.status()).toEqual(403);

        // The listing page should display an error message
        await page.goto('/front/entity.php');
        // We have no control on the toast markup
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('div.toast-container .toast-body'))
            .toBeAttached()
        ;

        // We switch context to be recursive on the subentity.
        await entity.switchToWithRecursion(subentity_id);

        await page.goto('/front/entity.form.php');
        form_sent = page.waitForResponse((response) =>
            response.url().includes('/front/entity.form.php')
            && response.request().method() === 'POST'
        );
        await page.getByLabel('Name').fill(`Sub-${subentity_name}`);
        await glpi_page.getButton("Add").click();
        expect((await form_sent).status()).toEqual(302);

        // TODO: the three created entities are not purged. `Entity` can not be
        // deleted through the API ("You don't have permission to perform this
        // action"), so there is no cleanup available today. They all live
        // inside the worker entity, so they do not leak to the other workers.
    });
});
