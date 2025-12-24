/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
import { EntityPage } from '../../pages/EntityPage';
import { EntityPageTabs } from '../../pages/EntityPageTabs';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('Can configure assistance properties', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const entity_page = new EntityPage(page);

    // Create and go to a new entity
    const id = await api.createItem('Entity', {
        'name': `Test entity ${crypto.randomUUID()}`,
        'entities_id': getWorkerEntityId(),
    });
    await entity_page.goto(id, EntityPageTabs.Assistance);

    // Change the target setting and save
    await expect(entity_page.show_tickets_on_helpdesk_dropdown)
        .toHaveText('Inheritance of the parent entity')
    ;
    await entity_page.doSetDropdownValue(
        entity_page.show_tickets_on_helpdesk_dropdown,
        'Yes'
    );
    await entity_page.save_button.click();

    // Value should be modified
    await expect(entity_page.show_tickets_on_helpdesk_dropdown).toHaveText('Yes');
});

test('Survey options change by type and rate', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const entity_page = new EntityPage(page);

    await entity_page.goto(getWorkerEntityId(), EntityPageTabs.Assistance);

    // Test Internal survey with default rate
    await entity_page.doSetDropdownValue(
        entity_page.survey_config_dropdown,
        'Internal survey'
    );
    await expect.soft(entity_page.create_after_dropdown).toBeVisible();
    await expect.soft(entity_page.rate_dropdown).toBeVisible();
    await expect.soft(entity_page.duration_dropdown).toBeHidden();
    await expect.soft(entity_page.max_rate_dropdown).toBeHidden();
    await expect.soft(entity_page.default_rate_input).toBeHidden();
    await expect.soft(entity_page.comment_required_input).toBeHidden();
    await expect.soft(entity_page.closed_after_text).toBeHidden();
    await expect.soft(entity_page.valid_tags_text).toBeHidden();
    await expect.soft(entity_page.url_input).toBeHidden();

    // Test Internal survey with 10% rate
    await entity_page.doSetDropdownValue(entity_page.rate_dropdown, '10%');
    await expect.soft(entity_page.create_after_dropdown).toBeVisible();
    await expect.soft(entity_page.rate_dropdown).toBeVisible();
    await expect.soft(entity_page.duration_dropdown).toBeVisible();
    await expect.soft(entity_page.max_rate_dropdown).toBeVisible();
    await expect.soft(entity_page.default_rate_input).toBeVisible();
    await expect.soft(entity_page.comment_required_input).toBeVisible();
    await expect.soft(entity_page.closed_after_text).toBeVisible();
    await expect.soft(entity_page.valid_tags_text).toBeHidden();
    await expect.soft(entity_page.url_input).toBeHidden();

    // Test External survey with default rate
    await entity_page.doSetDropdownValue(
        entity_page.survey_config_dropdown,
        'External survey'
    );
    await expect.soft(entity_page.create_after_dropdown).toBeVisible();
    await expect.soft(entity_page.rate_dropdown).toBeVisible();
    await expect.soft(entity_page.duration_dropdown).toBeHidden();
    await expect.soft(entity_page.max_rate_dropdown).toBeHidden();
    await expect.soft(entity_page.default_rate_input).toBeHidden();
    await expect.soft(entity_page.comment_required_input).toBeHidden();
    await expect.soft(entity_page.closed_after_text).toBeHidden();
    await expect.soft(entity_page.valid_tags_text).toBeHidden();
    await expect.soft(entity_page.url_input).toBeHidden();

    // Test External survey with 10% rate
    await entity_page.doSetDropdownValue(entity_page.rate_dropdown, '10%');
    await expect.soft(entity_page.create_after_dropdown).toBeVisible();
    await expect.soft(entity_page.rate_dropdown).toBeVisible();
    await expect.soft(entity_page.duration_dropdown).toBeVisible();
    await expect.soft(entity_page.max_rate_dropdown).toBeVisible();
    await expect.soft(entity_page.default_rate_input).toBeVisible();
    await expect.soft(entity_page.comment_required_input).toBeVisible();
    await expect.soft(entity_page.closed_after_text).toBeVisible();
    await expect.soft(entity_page.valid_tags_text).toBeVisible();
    await expect.soft(entity_page.url_input).toBeVisible();
});
