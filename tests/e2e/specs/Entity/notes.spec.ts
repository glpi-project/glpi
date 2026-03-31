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
import { EntityPage } from '../../pages/EntityPage';
import { EntityPageTabs } from '../../pages/EntityPageTabs';
import { TicketPage } from '../../pages/TicketPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('Can add a note', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const entity_page = new EntityPage(page);

    // Create an entity
    const id = await api.createItem('Entity', {
        'name': `Entity ${crypto.randomUUID()}`,
        'entities_id': getWorkerEntityId(),
    });

    // Go to the entity and add a note
    await entity_page.goto(id, EntityPageTabs.Notes);
    await entity_page.doAddNote("My test note");

    // A note should have been created
    await expect(entity_page.notes).toHaveCount(1);
    await expect(entity_page.notes_content).toHaveText(["My test note"]);
});

test('Can delete a note', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const entity_page = new EntityPage(page);

    // Create an entity and add a note
    const id = await api.createItem('Entity', {
        'name': `Entity ${crypto.randomUUID()}`,
        'entities_id': getWorkerEntityId(),
    });
    await api.createItem('Notepad', {
        'itemtype': 'Entity',
        'items_id': id,
        'content': 'My note',
    });

    // Go to the entity and delete the note
    await entity_page.goto(id, EntityPageTabs.Notes);
    await expect(entity_page.notes).toHaveCount(1);
    await entity_page.doDeleteNote();

    // The note is now deleted
    await expect(entity_page.notes).toHaveCount(0);
});

test('Can edit a note', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const entity_page = new EntityPage(page);

    // Create an entity and add a note
    const id = await api.createItem('Entity', {
        'name': `Entity ${crypto.randomUUID()}`,
        'entities_id': getWorkerEntityId(),
    });
    await api.createItem('Notepad', {
        'itemtype': 'Entity',
        'items_id': id,
        'content': 'My note',
    });

    // Go to the entity and update the note
    await entity_page.goto(id, EntityPageTabs.Notes);
    await expect(entity_page.notes).toHaveCount(1);
    await entity_page.doUpdateNoteContent(0, "Edited value");

    // The note has been updated
    await expect(entity_page.notes_content).toHaveText(["Edited value"]);
});

test('Can add a file to a note', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const entity_page = new EntityPage(page);

    // Create an entity and add a note
    const id = await api.createItem('Entity', {
        'name': `Entity ${crypto.randomUUID()}`,
        'entities_id': getWorkerEntityId(),
    });
    await api.createItem('Notepad', {
        'itemtype': 'Entity',
        'items_id': id,
        'content': 'My note',
    });

    // Go to the entity and update the note
    await entity_page.goto(id, EntityPageTabs.Notes);
    await expect(entity_page.notes).toHaveCount(1);
    await entity_page.doAddFileToNote(0, "uploads/bar.txt");

    // The note should now have a linked document
    await expect(entity_page.getLink('File extension bar.txt'))
        .toBeAttached()
    ;
});

test('Can view note on ticket', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const entity_page = new EntityPage(page);
    const ticket_page = new TicketPage(page);

    // Create an entity and add a note with an attachment
    const entity_name = `Entity ${crypto.randomUUID()}`;
    const entities_id = await api.createItem('Entity', {
        'name': entity_name,
        'entities_id': getWorkerEntityId(),
    });
    await api.createItem('Notepad', {
        'itemtype': 'Entity',
        'items_id': entities_id,
        'content': 'My note',
        'visible_from_ticket': true,
    });
    await entity_page.goto(entities_id, EntityPageTabs.Notes);
    await expect(entity_page.notes).toHaveCount(1);
    await entity_page.doAddFileToNote(0, "uploads/bar.txt");

    // Go to the ticket page and make sure the note is visible
    await ticket_page.gotoCreationPage();
    await ticket_page.doSetEntityDropdown(entity_name);
    await expect(ticket_page.notes_area).toContainText("My note");
    await expect(ticket_page.notes_area).toContainText("bar.txt");
});
