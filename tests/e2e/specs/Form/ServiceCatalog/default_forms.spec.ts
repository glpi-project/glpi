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
import { expect, test } from '../../../fixtures/glpi_fixture';
import { GlpiPage } from '../../../pages/GlpiPage';
import { Api } from '../../../utils/Api';
import { Profiles } from '../../../utils/Profiles';
import {
    getWorkerEntityId,
    getWorkerUserId,
} from '../../../utils/WorkerEntities';

// The four scenarios all submit one of the two built-in forms, which live in
// the root entity and are therefore shared by every worker. Cypress ran them
// one after the other; running them in parallel makes two workers submit the
// same form at the same time, which intermittently fails server side
// ("Failed to submit form, please contact your administrator.").
test.describe.configure({ mode: 'serial' });

test.describe('Default forms', () => {
    const setupFixtures = async (api: Api): Promise<string> => {
        const uuid = randomUUID();

        await api.createItem('ITILCategory', {
            'name': `Test ITILCategory - ${uuid}`,
            'entities_id': getWorkerEntityId(),
        });
        await api.createItem('Computer', {
            'name': `Test Computer - ${uuid}`,
            'users_id': getWorkerUserId(),
            'entities_id': getWorkerEntityId(),
        });
        await api.createItem('Location', {
            'name': `Test Location - ${uuid}`,
            'entities_id': getWorkerEntityId(),
        });

        return uuid;
    };

    const scenarios = [
        { profile: Profiles.SuperAdmin, profile_name: 'Super-Admin', form_id: 1 },
        { profile: Profiles.SelfService, profile_name: 'Self-Service', form_id: 1 },
        { profile: Profiles.SuperAdmin, profile_name: 'Super-Admin', form_id: 2 },
        { profile: Profiles.SelfService, profile_name: 'Self-Service', form_id: 2 },
    ];

    for (const scenario of scenarios) {
        test(`can fill and submit form ${scenario.form_id} as ${scenario.profile_name}`, async ({
            page,
            profile,
            api,
        }) => {
            const uuid = await setupFixtures(api);
            const glpi_page = new GlpiPage(page);

            await profile.set(scenario.profile);

            await page.goto(`/Form/Render/${scenario.form_id}`);

            await glpi_page.doSetDropdownValue(
                glpi_page.getDropdownByLabel('Urgency'),
                'High'
            );
            await glpi_page.doSearchAndClickDropdownValue(
                glpi_page.getDropdownByLabel('Category'),
                `Test ITILCategory - ${uuid}`
            );
            await glpi_page.doSearchAndClickDropdownValue(
                glpi_page.getDropdownByLabel('User devices'),
                `Test Computer - ${uuid}`,
                false
            );
            await glpi_page.doSearchAndClickDropdownValue(
                glpi_page.getDropdownByLabel('Observers'),
                'glpi',
                false
            );
            await glpi_page.doSearchAndClickDropdownValue(
                glpi_page.getDropdownByLabel('Location'),
                `Test Location - ${uuid}`
            );

            await glpi_page.getTextbox("Title").fill("My title");

            const description_region = glpi_page.getRegion("Description");
            // Exact match: the question also holds a "Question
            // description" note, which a partial match would catch.
            const description = await glpi_page.initRichTextByLabel(
                "Description",
                description_region,
                true
            );
            await description.fill("My description");

            await glpi_page.getButton("Submit").click();

            const alert = glpi_page.getAlert('Item successfully created');
            await expect(alert).toBeVisible();

            const href = await alert.getByRole('link').getAttribute('href');
            const id = /\?id=(.*)/.exec(href ?? '')?.[1];
            const fields = await api.getItem('Ticket', Number(id));
            expect(fields.urgency).toEqual(4);
            expect(fields.name).toEqual('My title');
            expect(fields.content).toEqual('<p>My description</p>');
        });
    }
});
