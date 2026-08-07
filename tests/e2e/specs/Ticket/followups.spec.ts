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

import { expect, test } from '../../fixtures/glpi_fixture';
import { TicketPage } from '../../pages/TicketPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe('ITIL Followups', () => {
    test('Can add a followup to a new ticket', async ({
        profile,
        page,
        api
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const id = await api.createItem('Ticket', {
            name: 'Open ticket',
            content: '',
            entities_id: getWorkerEntityId(),
        });
        const ticket = new TicketPage(page);
        await ticket.goto(id);
        await expect(ticket.getButton('Answer')).toBeVisible();
    });

    test("Can't add a followup to a closed ticket", async ({
        profile,
        page,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const id = await api.createItem('Ticket', {
            name: 'Closed ticket',
            content: '',
            status: 6,
            entities_id: getWorkerEntityId(),
        });

        const ticket = new TicketPage(page);
        await ticket.goto(id);
        await expect(ticket.getButton('Answer')).not.toBeAttached();
    });
});
