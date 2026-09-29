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

import { test, expect } from '../../fixtures/glpi_fixture';
import { ImpactPage } from '../../pages/ImpactPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('Impact graph loads', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const computer_id = await api.createItem('Computer', {
        name: 'Impact computer',
        entities_id: getWorkerEntityId(),
    });

    const impact_page = new ImpactPage(page);
    await impact_page.gotoComputerImpact(computer_id);

    await expect(impact_page.graph_view).toBeVisible();
    await expect(impact_page.list_view).toBeHidden();
    expect(await impact_page.canvas_elements.count()).toBeGreaterThanOrEqual(1);

    await impact_page.view_as_list_button.click();
    await expect(impact_page.graph_view).toBeHidden();
    await expect(impact_page.list_view).toBeVisible();

    await impact_page.view_as_graph_button.click();
    await expect(impact_page.graph_view).toBeVisible();
    await expect(impact_page.list_view).toBeHidden();
});

test('Saving a group twice without reloading the page (#25192)', async ({ page, profile, api }) => {
    // Regression: adding an asset to an already-saved group used to fail with
    // "Data truncated for column 'parent_id'" (stale temp compound id).
    await profile.set(Profiles.SuperAdmin);
    const entities_id = getWorkerEntityId();
    const main_asset_id = await api.createItem('Computer', { name: 'Impact main asset', entities_id });
    const grouped_asset_a_id = await api.createItem('Computer', { name: 'Impact grouped asset A', entities_id });
    const grouped_asset_b_id = await api.createItem('Computer', { name: 'Impact grouped asset B', entities_id });
    const late_asset_id = await api.createItem('Computer', { name: 'Impact late asset', entities_id });

    const impact_page = new ImpactPage(page);
    await impact_page.gotoComputerImpact(main_asset_id);
    await expect(impact_page.graph_view).toBeVisible();

    // Canvas has no DOM handles, so drive the graph through GLPIImpact's own API.
    await page.evaluate(([a, b]) => {
        // @ts-expect-error GLPIImpact is a legacy global set by js/impact.js
        window.GLPIImpact.addNode(a, 'Computer', { x: 200, y: 0 });
        // @ts-expect-error GLPIImpact is a legacy global set by js/impact.js
        window.GLPIImpact.addNode(b, 'Computer', { x: 200, y: 150 });
    }, [grouped_asset_a_id, grouped_asset_b_id]);
    await expect.poll(() => page.evaluate(([a, b]) => {
        // @ts-expect-error GLPIImpact is a legacy global set by js/impact.js
        const GLPIImpact = window.GLPIImpact;
        return GLPIImpact.cy.getElementById(`Computer::${a}`).length
            + GLPIImpact.cy.getElementById(`Computer::${b}`).length;
    }, [grouped_asset_a_id, grouped_asset_b_id])).toBe(2);

    // Group the two assets, same as GLPIImpact.addCompoundFromSelection().
    const tmpCompoundId = await page.evaluate(([a, b]) => {
        // @ts-expect-error GLPIImpact is a legacy global set by js/impact.js
        const GLPIImpact = window.GLPIImpact;
        const compound = GLPIImpact.cy.add({ group: 'nodes', data: { color: '#dadada', label: 'E2E group' } });
        GLPIImpact.cy.getElementById(`Computer::${a}`).move({ parent: compound.id() });
        GLPIImpact.cy.getElementById(`Computer::${b}`).move({ parent: compound.id() });
        GLPIImpact.updateFlags();
        return compound.id();
    }, [grouped_asset_a_id, grouped_asset_b_id]);

    // First save: the server creates the compound row and must report back its real id.
    const first_save_response_promise = page.waitForResponse(
        (resp) => resp.url().includes('ajax/impact.php') && resp.request().method() === 'POST'
    );
    await impact_page.save_button.click();
    const first_save_response = await first_save_response_promise;
    expect(first_save_response.status()).toBe(200);
    const first_save_body = await first_save_response.json();
    expect(Object.keys(first_save_body.compounds_mapping)).toContain(tmpCompoundId);
    const realCompoundId = first_save_body.compounds_mapping[tmpCompoundId];

    // The live graph must now reference the real id: the temporary one is gone
    await expect.poll(() => page.evaluate((id) => {
        // @ts-expect-error GLPIImpact is a legacy global set by js/impact.js
        return window.GLPIImpact.cy.getElementById(id).length;
    }, tmpCompoundId)).toBe(0);

    // Add a third asset without reloading; wait for the node itself (not a
    // count, which the compound node would already satisfy) to avoid racing addNode().
    await page.evaluate((assetId) => {
        // @ts-expect-error GLPIImpact is a legacy global set by js/impact.js
        window.GLPIImpact.addNode(assetId, 'Computer', { x: 400, y: 75 });
    }, late_asset_id);
    await expect.poll(() => page.evaluate((assetId) => {
        // @ts-expect-error GLPIImpact is a legacy global set by js/impact.js
        return window.GLPIImpact.cy.getElementById(`Computer::${assetId}`).length;
    }, late_asset_id)).toBe(1);

    await page.evaluate(([assetId, parentId]) => {
        // @ts-expect-error GLPIImpact is a legacy global set by js/impact.js
        const GLPIImpact = window.GLPIImpact;
        GLPIImpact.cy.getElementById(`Computer::${assetId}`).move({ parent: String(parentId) });
        GLPIImpact.updateFlags();
    }, [late_asset_id, realCompoundId]);

    // Second save: this is where the bug reproduced (stale temp compound id).
    const second_save_response_promise = page.waitForResponse(
        (resp) => resp.url().includes('ajax/impact.php') && resp.request().method() === 'POST'
    );
    await impact_page.save_button.click();
    const second_save_response = await second_save_response_promise;
    expect(second_save_response.status()).toBe(200);

    // The late asset ended up in the group under its real (integer) parent id
    await expect.poll(() => page.evaluate((assetId) => {
        // @ts-expect-error GLPIImpact is a legacy global set by js/impact.js
        return window.GLPIImpact.cy.getElementById(`Computer::${assetId}`).data('parent');
    }, late_asset_id)).toBe(String(realCompoundId));
});
