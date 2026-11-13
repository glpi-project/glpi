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

test('Inline positioning in timeline content does not escape the timeline', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const id = await api.createItem('Ticket', {
        name: 'Ticket with pasted positioned content',
        content: '<p>before</p>'
            + '<div style="position: absolute; top: 0px; left: 0px; transform: translateY(400px);">pasted line</div>'
            + '<div class="position-fixed top-0 translate-middle">bootstrap line</div>'
            + '<div style="translate: 0 400px;">translated line</div>'
            + '<p>after</p>',
        entities_id: getWorkerEntityId(),
    });

    const ticket = new TicketPage(page);
    await ticket.goto(id);

    const pasted_line = page.getByTestId('itil-timeline').getByText('pasted line');
    await expect(pasted_line).toHaveCSS('position', 'static');
    await expect(pasted_line).toHaveCSS('transform', 'none');

    const bootstrap_line = page.getByTestId('itil-timeline').getByText('bootstrap line');
    await expect(bootstrap_line).toHaveCSS('position', 'static');
    await expect(bootstrap_line).toHaveCSS('transform', 'none');

    const translated_line = page.getByTestId('itil-timeline').getByText('translated line');
    await expect(translated_line).toHaveCSS('translate', 'none');
});
