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
import { Api } from '../../utils/Api';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe('Webhooks', () => {
    const setupWebhook = async (api: Api): Promise<number> => {
        return await api.createItem('Webhook', {
            name: `New computer ${randomUUID()}`,
            entities_id: getWorkerEntityId(),
            itemtype: 'Computer',
            event: 'new',
        });
    };

    test('Payload editor switches', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const webhook_id = await setupWebhook(api);
        await page.goto(`/front/webhook.form.php?id=${webhook_id}`);
        await glpi_page.doGoToTab('Payload editor');

        const tabpanel = page.getByRole('tabpanel');

        await expect(tabpanel.getByRole('button', { name: 'Save' }))
            .toBeVisible()
        ;
        await expect(tabpanel.getByRole('button', { name: 'Search' }))
            .not.toBeAttached()
        ;

        // eslint-disable-next-line playwright/no-raw-locators
        const default_payload = tabpanel.locator('textarea[name="default_payload"]');
        // eslint-disable-next-line playwright/no-raw-locators
        const payload = tabpanel.locator('#payload');

        await expect(default_payload).toBeVisible();
        await expect(default_payload).toHaveAttribute('readonly');
        await expect(payload).toBeHidden();

        await tabpanel.getByLabel('Use default payload').click();
        await expect(tabpanel.getByRole('button', { name: 'Save' }))
            .toBeVisible()
        ;
        await expect(tabpanel.getByRole('button', { name: 'Search' }))
            .toBeVisible()
        ;
        await expect(default_payload).toBeHidden();
        await expect(payload).toBeVisible();

        // Ensure the monaco editor has a usable size. If initialized with a
        // hidden parent, it can have a size less then 10px by 10px.
        // eslint-disable-next-line playwright/no-raw-locators
        const monaco = payload.locator('.monaco-editor');
        const box = await monaco.boundingBox();
        expect(box?.width).toBeGreaterThanOrEqual(100);
        expect(box?.height).toBeGreaterThanOrEqual(100);
    });

    test('has secret', async ({ page, profile, api }) => {
        // primarily used to test the copy/disclose buttons for password fields
        await profile.set(Profiles.SuperAdmin);
        await page.context().grantPermissions([
            'clipboard-read',
            'clipboard-write',
        ]);
        const glpi_page = new GlpiPage(page);

        const webhook_id = await setupWebhook(api);
        await page.goto(`/front/webhook.form.php?id=${webhook_id}`);
        await glpi_page.doGoToTab('Security');

        const tabpanel = page.getByRole('tabpanel');
        // `\s*` is needed because playwright does not normalize whitespaces
        // when matching with a regular expression, and the label content is
        // indented.
        const secret = tabpanel.getByLabel(/^\s*Secret/);

        // eslint-disable-next-line playwright/no-raw-locators
        const disclose_button = secret.locator('+ *');
        // eslint-disable-next-line playwright/no-raw-locators
        const copy_button = secret.locator('+ * + *');

        await expect(secret).toHaveAttribute('type', 'password');
        await disclose_button.dispatchEvent('mousedown');
        await expect(secret).toHaveAttribute('type', 'text');
        await disclose_button.dispatchEvent('mouseup');
        await expect(secret).toHaveAttribute('type', 'password');

        await copy_button.click();
        const secret_value = await secret.inputValue();
        expect(secret_value.length).toBeGreaterThan(0);

        // should be copied to clipboard
        const clip_text = await page.evaluate(
            () => navigator.clipboard.readText()
        );
        expect(clip_text).toEqual(secret_value);
    });
});
