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

import type { Page } from '@playwright/test';
import { expect, test } from '../../fixtures/glpi_fixture';
import { TicketPage } from '../../pages/TicketPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

async function expectRightSideInTicketLayout(page: Page): Promise<void>
{
    // An unbalanced closing tag in the timeline moves the right side panel out of the
    // ticket layout, below the timeline.
    await expect(page.getByTestId('itil-object').getByTestId('itil-right-side')).toBeAttached();
}

test.describe('Timeline satisfaction survey', () => {
    test('Right side panel stays in the layout without satisfaction survey', async ({ profile, page, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const ticket_id = await api.createItem('Ticket', {
            name: 'Closed ticket without satisfaction survey',
            content: 'test',
            status: 6,
            entities_id: getWorkerEntityId(),
        });

        const ticket = new TicketPage(page);
        await ticket.goto(ticket_id);

        await expectRightSideInTicketLayout(page);
        await expect(page.getByTestId('survey')).not.toBeAttached();
    });

    test('Satisfaction survey is rendered once, inside the timeline', async ({ profile, page, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const ticket_id = await api.createItem('Ticket', {
            name: 'Closed ticket with satisfaction survey',
            content: 'test',
            status: 6,
            entities_id: getWorkerEntityId(),
        });
        await api.createItem('TicketSatisfaction', {
            tickets_id: ticket_id,
            type: 1, // Internal survey
            date_begin: new Date().toISOString().slice(0, 19).replace('T', ' '),
        });

        const ticket = new TicketPage(page);
        await ticket.goto(ticket_id);

        await expectRightSideInTicketLayout(page);
        await expect(page.getByTestId('survey')).toHaveCount(1);
        await expect(page.getByTestId('itil-timeline').getByTestId('survey')).toHaveCount(1);
    });
});
