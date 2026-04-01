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
import { test, expect } from '../../../fixtures/glpi_fixture';
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId, getWorkerIndex, getWorkerUserId } from '../../../utils/WorkerEntities';
import { FormPage } from "../../../pages/FormPage";

const actor_types = [
    {
        name: 'Requester',
        fixture: 'destination_config_fields/requester-config.json',
        data_attr: 'requester',
        default_value: 'User who filled the form',
        group_right_field: 'is_requester',
    },
    {
        name: 'Assignee',
        fixture: 'destination_config_fields/assignee-config.json',
        data_attr: 'assign',
        default_value: 'From template',
        group_right_field: 'is_assign',
    },
    {
        name: 'Observer',
        fixture: 'destination_config_fields/observer-config.json',
        data_attr: 'observer',
        default_value: 'From template',
        group_right_field: 'is_watcher',
    },
];

for (const actor_type of actor_types) {
    test.describe(`${actor_type.name} configuration`, () => {
        let form_page: FormPage;
        const unique = randomUUID();
        const authorized_actor_name = `Test ${actor_type.name} authorized - ${unique}`;
        const unauthorized_actor_name = `Test ${actor_type.name} unauthorized - ${unique}`;
        const authorized_group_name = `Test ${actor_type.name} group authorized - ${unique}`;
        const unauthorized_group_name = `Test ${actor_type.name} group unauthorized - ${unique}`;

        test.beforeEach(async ({ page, profile, api, formImporter }) => {
            await profile.set(Profiles.SuperAdmin);
            form_page = new FormPage(page);
            const entity_id = getWorkerEntityId();

            const info = await formImporter.importForm(actor_type.fixture);

            // Create a possible actor
            const actor_id = await api.createItem('User', {
                name: authorized_actor_name,
            });
            await api.createItem('Profile_User', {
                users_id: actor_id,
                profiles_id: actor_type.name === 'Assignee' ? Profiles.Technician : Profiles.SelfService,
                entities_id: entity_id,
                is_recursive: 1,
            });

            if (actor_type.name === 'Assignee') {
                const unauthorized_actor_id = await api.createItem('User', {
                    name: unauthorized_actor_name,
                });
                await api.createItem('Profile_User', {
                    users_id: unauthorized_actor_id,
                    profiles_id: Profiles.SelfService,
                    entities_id: entity_id,
                    is_recursive: 1,
                });
            }

            const no_group_rights = {
                is_requester: 0,
                is_watcher: 0,
                is_assign: 0,
            };
            const authorized_group_right = {
                [actor_type.group_right_field]: 1,
            };

            // Create groups for authorized and unauthorized checks
            const authorized_group_id = await api.createItem('Group', {
                name: authorized_group_name,
                entities_id: entity_id,
                ...no_group_rights,
                ...authorized_group_right,
            });
            await api.createItem('Group', {
                name: unauthorized_group_name,
                entities_id: entity_id,
                ...no_group_rights,
            });

            // Create a Computer with assigned user and group
            const worker_user_id = getWorkerUserId();
            await api.createItem('Computer', {
                name: `Test Computer - ${unique}`,
                entities_id: entity_id,
                users_id: worker_user_id,
                users_id_tech: worker_user_id,
                groups_id: authorized_group_id,
                groups_id_tech: authorized_group_id,
            });

            await form_page.gotoDestinationTab(info.getId());
        });

        test('Can use all possible configuration options', async () => {
            await form_page.doOpenDestinationAccordionItem('Actors');

            const config = form_page.getRegion(`${actor_type.name}s configuration`);
            const strategy_dropdown = form_page.getStrategyDropdown(config);

            // Default value
            await expect(strategy_dropdown).toHaveText(actor_type.default_value);

            // Make sure hidden dropdowns are not displayed
            await expect(form_page.getDropdownByLabel('Select actors...', config)).toBeHidden();
            await expect(form_page.getDropdownByLabel('Select questions...', config)).toBeHidden();

            const options = [
                'From template',
                'User that own an asset from an answer',
                'User who filled the form',
                'Group that own an asset from an answer',
                'Specific actors',
                'Answer from specific questions',
                'Group in charge of an asset from an answer',
                `Answer to last "${actor_type.name}s" or "Email" question`,
                'Technician in charge of an asset from an answer',
                'Supervisor of the user who filled the form',
            ];

            for (const option of options) {
                await form_page.doSetDropdownValue(strategy_dropdown, option);

                /* eslint-disable playwright/no-conditional-in-test */
                if (option === 'Specific actors') {
                    const actors_dropdown = form_page.getDropdownByLabel('Select actors...', config);
                    await form_page.doSearchAndClickDropdownValue(actors_dropdown, authorized_actor_name, false);
                } else if (option === 'Answer from specific questions') {
                    const questions_dropdown = form_page.getDropdownByLabel('Select questions...', config);
                    await form_page.doSetDropdownValue(questions_dropdown, `My ${actor_type.name} question`);
                } else if (
                    option === 'User that own an asset from an answer'
                    || option === 'Technician in charge of an asset from an answer'
                    || option === 'Group that own an asset from an answer'
                    || option === 'Group in charge of an asset from an answer'
                ) {
                    const items_dropdown = form_page.getDropdownByLabel('Select questions...', config);
                    await form_page.doSetDropdownValue(items_dropdown, `My Computer question`);
                }

                await form_page.doSaveDestinationAndReopenAccordion('Actors');
                await expect(strategy_dropdown).toHaveText(option);
            }
        });

        test('Cannot select unauthorized actors with "Specific actors"', async () => {
            await form_page.doOpenDestinationAccordionItem('Actors');

            const config = form_page.getRegion(`${actor_type.name}s configuration`);
            const strategy_dropdown = form_page.getStrategyDropdown(config);
            await form_page.doSetDropdownValue(strategy_dropdown, 'Specific actors');

            const actors_dropdown = form_page.getDropdownByLabel('Select actors...', config);
            await form_page.doSearchAndClickDropdownValue(actors_dropdown, authorized_group_name, false);

            await form_page.doAssertDropdownValueIsNotAvailable(actors_dropdown, unauthorized_group_name);

            if (actor_type.name === 'Assignee') {
                await form_page.doAssertDropdownValueIsNotAvailable(actors_dropdown, unauthorized_actor_name);
            }
        });

        test('Can create ticket using default configuration', async ({ page }) => {
            // Set to "User who filled the form"
            await form_page.doOpenDestinationAccordionItem('Actors');
            const config = form_page.getRegion(`${actor_type.name}s configuration`);
            const strategy_dropdown = form_page.getStrategyDropdown(config);
            await form_page.doSetDropdownValue(strategy_dropdown, 'User who filled the form');
            await form_page.doSaveDestinationAndReopenAccordion('Actors');

            // Go to form tab
            await page.getByRole('tab', { name: 'Form', exact: true }).click();

            // Preview and submit the form
            await form_page.doPreviewForm();
            await form_page.getButton('Submit').click();

            // Click on the created ticket link
            await page.getByRole('link', { name: `Test ${actor_type.name.toLowerCase()} config` }).click();

            // Verify actor in ticket
            const actors_region = form_page.getRegion('Actors');
            const actor_select = actors_region.getByTestId(`select-${actor_type.data_attr}`);
            const worker_index = getWorkerIndex();
            const worker_name = `E2E worker account ${String(worker_index).padStart(2, '0')}`;
            await expect(actor_select).toContainText(worker_name);
        });
    });
}
