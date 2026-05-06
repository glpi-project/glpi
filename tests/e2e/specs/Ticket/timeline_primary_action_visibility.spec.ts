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

/**
 * Tests for the UX behavior that hides the global Save button (#right-actions)
 * whenever a timeline answer form is open, to prevent the user from clicking the wrong
 * primary action button. The button must be restored once the form is closed.
 */

import type { Page } from '@playwright/test';
import { expect, test } from '../../fixtures/glpi_fixture';
import { TicketPage } from '../../pages/TicketPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

type OpenForm = (ticket: TicketPage, page: Page) => Promise<void>;

type FormCase = {
    form: string;
    block: string;
    open: OpenForm;
};

const form_cases: FormCase[] = [
    {
        form: 'followup',
        block: 'new-ITILFollowup-block',
        open: async (ticket) => {
            await ticket.getButton('Answer').click();
        },
    },
    {
        form: 'task',
        block: 'new-TicketTask-block',
        open: async (ticket, page) => {
            await ticket.getButton('View other actions').click();
            await page.getByRole('listitem', { name: 'Create a task' }).click();
        },
    },
    {
        form: 'solution',
        block: 'new-ITILSolution-block',
        open: async (ticket, page) => {
            await ticket.getButton('View other actions').click();
            await page.getByRole('listitem', { name: 'Add a solution' }).click();
        },
    },
    {
        form: 'document',
        block: 'new-Document_Item-block',
        open: async (ticket, page) => {
            await ticket.getButton('View other actions').click();
            await page.getByRole('listitem', { name: 'Add a document' }).click();
        },
    },
    {
        form: 'validation',
        block: 'new-TicketValidation-block',
        open: async (ticket, page) => {
            await ticket.getButton('View other actions').click();
            await page.getByRole('listitem', { name: 'Ask for approval' }).click();
        },
    },
];

for (const { form, block, open } of form_cases) {
    test(`Save button is hidden while ${form} form is open and restored on close`, async ({ profile, page, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const ticket_id = await api.createItem('Ticket', {
            name: `Test primary action visibility with ${form} form`,
            content: 'test',
            entities_id: getWorkerEntityId(),
        });

        const ticket = new TicketPage(page);
        await ticket.goto(ticket_id);

        await expect(ticket.getButton('Save')).toBeVisible();

        await open(ticket, page);
        await expect(page.getByTestId(block)).toHaveClass(/\bshow\b/);

        await expect(ticket.getButton('Save')).toBeHidden();

        await page.getByTestId(block).getByRole('button', { name: 'Close' }).click();
        await expect(page.getByTestId(block)).not.toHaveClass(/\bshow\b/);

        await expect(ticket.getButton('Save')).toBeVisible();
    });
}
