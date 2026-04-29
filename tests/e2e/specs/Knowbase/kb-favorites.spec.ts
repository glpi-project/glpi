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

import { randomUUID } from "crypto";
import { expect, test } from "../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../pages/KnowbaseItemPage";
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from "../../utils/WorkerEntities";

test('Can toggle favorites from false to true', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const name = `My kb entry for favorites test - ${randomUUID()}`;
    const id = await api.createItem('KnowbaseItem', {
        name: name,
        entities_id: getWorkerEntityId(),
        answer: "My answer",
    });

    await kb.goto(id);
    await page.getByTitle('More actions').click();

    const favorite_toggle = kb.getButton('Add to favorites');
    await expect(favorite_toggle.getByRole('checkbox')).not.toBeChecked();
    await expect(kb.getFavoriteArticle(name)).toBeHidden();

    await kb.doToggleFavoriteStatus();
    await expect(favorite_toggle.getByRole('checkbox')).toBeChecked();
    await expect(kb.getFavoriteArticle(name)).toBeVisible();

    await page.reload();
    await page.getByTitle('More actions').click();
    const favorite_toggle_after_reload = kb.getButton('Add to favorites');
    await expect(favorite_toggle_after_reload.getByRole('checkbox')).toBeChecked();
    await expect(kb.getFavoriteArticle(name)).toBeVisible();
});

test('Can toggle favorites from true to false', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const name = `My kb entry for favorites removal test - ${randomUUID()}`;
    const id = await api.createItem('KnowbaseItem', {
        name: name,
        entities_id: getWorkerEntityId(),
        answer: "My answer",
    });

    await kb.goto(id);
    await page.getByTitle('More actions').click();
    await kb.doToggleFavoriteStatus();

    await page.reload();
    await page.getByTitle('More actions').click();

    const favorite_toggle = kb.getButton('Add to favorites');
    await expect(favorite_toggle.getByRole('checkbox')).toBeChecked();
    await expect(kb.getFavoriteArticle(name)).toBeVisible();

    await kb.doToggleFavoriteStatus();
    await expect(favorite_toggle.getByRole('checkbox')).not.toBeChecked();
    await expect(kb.getFavoriteArticle(name)).toBeHidden();

    await page.reload();
    await page.getByTitle('More actions').click();
    const favorite_toggle_after_reload = kb.getButton('Add to favorites');
    await expect(favorite_toggle_after_reload.getByRole('checkbox')).not.toBeChecked();
    await expect(kb.getFavoriteArticle(name)).toBeHidden();
});
