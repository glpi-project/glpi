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

test.describe('Filterable', () => {
    test('preview results are only loaded when explicitly requester', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        // We will be looking for the computer name directly so it must be unique.
        const uuid = randomUUID();
        const computer_name = `Computer for Filterable tests [${uuid}]`;

        await test.step("Set up data and go to webhook page", async () => {
            await api.createItem("Computer", {
                "name": computer_name,
                "entities_id": getWorkerEntityId(),
            });
            const webhook_id = await api.createItem("Webhook", {
                "name": `Test webhook - ${uuid}`,
                "itemtype": "Computer",
                "entities_id": getWorkerEntityId(),
            });

            const url = `/front/webhook.form.php`;
            const tab = "Glpi\\Search\\CriteriaFilter$1";
            await page.goto(`${url}?id=${webhook_id}&forcetab=${tab}`);
        });

        await test.step("Create filter", async () => {
            await page.getByRole("button", { name: "Create a filter" }).click();

            // TODO: bad selector here, we must add accessiblity labels to
            // the search engine in order to be able to use getByRole instead.
            // -> glpi_page.getTextbox("Items seen")
            // eslint-disable-next-line playwright/no-raw-locators
            await page.locator('input[name="criteria[0][value]"]')
                .fill(computer_name)
            ;
            await page.getByRole("button", { name: "Save" }).click();
            await expect(glpi_page.getAlert("Filter saved")).toBeVisible();
        });

        await test.step("Check that the preview results are not loaded", async () => {
            await expect(glpi_page.getLink(computer_name)).not.toBeAttached();
        });

        await test.step("Load preview results", async () => {
            // Protecting against false positive by demonstrating that if the
            // preview was shown our computer link would be displayed.
            await page.getByRole("button", { name: "Preview results" }).click();
            await expect(glpi_page.getLink(computer_name)).toBeAttached();
        });

        await test.step("Reload page and make sure preview was not executed", async () => {
            await page.reload();

            // We will force the hidden preview content to be displayed as it
            // make it easier to query it with accessiblity selectors.
            // eslint-disable-next-line playwright/no-raw-locators
            const preview = page.locator("#criteria_filter_preview");
            await expect(preview).toBeAttached();
            await preview.evaluate((el) => el.classList.remove("d-none"));

            await expect(
                preview.getByRole("heading", { name: "Preview", exact: true })
            ).toBeAttached();

            // Computer link should not be present
            await expect(
                preview.getByRole("link", { name: computer_name, exact: true })
            ).not.toBeAttached();
        });
    });
});
