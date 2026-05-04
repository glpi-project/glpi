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

type OpenForm = (ticket: TicketPage, page: Page) => Promise<void>;

type WarningCase = {
    form: string;
    block: string;
    open: OpenForm;
};

const warning_cases: WarningCase[] = [
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

for (const { form, block, open } of warning_cases) {
    test(`warning appears and blocks save when ${form} form is open`, async ({ profile, page, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const ticket_id = await api.createItem('Ticket', {
            name: `Test warning with open ${form} form`,
            content: 'test',
            entities_id: getWorkerEntityId(),
        });

        const ticket = new TicketPage(page);
        await ticket.goto(ticket_id);
        await open(ticket, page);

        // Bootstrap Collapse goes: 'collapse' → 'collapsing' (350ms) → 'collapse show'.
        // The JS condition checks for '.collapse.show', so we must wait until the
        // animation completes before clicking Save.
        await expect(page.getByTestId(block)).toHaveClass(/\bshow\b/);

        let dialog_message = '';
        page.once('dialog', (dialog) => {
            dialog_message = dialog.message();
            void dialog.dismiss();
        });
        await ticket.getButton('Save').click();

        expect(dialog_message).toContain('unsaved changes');
        await expect(page).toHaveURL(new RegExp(`id=${ticket_id}`));
    });
}

test('no warning when saving ticket without open timeline form', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test no warning on save',
        content: 'test',
        entities_id: getWorkerEntityId(),
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);

    const save_response = page.waitForResponse(
        (resp) => resp.url().includes('/front/ticket.form.php') && resp.request().method() === 'POST'
    );
    await ticket.getButton('Save').click();
    await save_response;

    await expect(page.getByRole('alert')).toContainText('Item successfully updated');
});

test('no warning when saving ticket in waiting status', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test no warning on waiting ticket',
        content: 'test',
        status: 4,
        entities_id: getWorkerEntityId(),
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);

    const save_response = page.waitForResponse(
        (resp) => resp.url().includes('/front/ticket.form.php') && resp.request().method() === 'POST'
    );
    await ticket.getButton('Save').click();
    await save_response;

    await expect(page.getByRole('alert')).toContainText('Item successfully updated');
});
