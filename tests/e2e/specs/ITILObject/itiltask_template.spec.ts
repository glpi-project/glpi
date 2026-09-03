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
import { Locator, Page } from '@playwright/test';
import { randomUUID } from 'crypto';
import { expect, test } from '../../fixtures/glpi_fixture';
import { GlpiPage } from '../../pages/GlpiPage';
import { Api } from '../../utils/Api';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe("ITIL Task Template Preservation", () => {
    type Fixtures = {
        test_ticket_id: number,
        empty_template_name: string,
        pending_reason_name: string,
        with_content_template_name: string,
    };

    const setupFixtures = async (api: Api): Promise<Fixtures> => {
        const unique_id = randomUUID();
        const empty_template_name = `Empty Task Template ${unique_id}`;
        const pending_reason_name = `Test Task Pending Reason ${unique_id}`;
        const with_content_template_name = `Task Template with Content ${unique_id}`;

        const test_ticket_id = await api.createItem('Ticket', {
            name: `Test ticket for task templates ${unique_id}`,
            content: 'Test ticket',
            entities_id: getWorkerEntityId(),
        });

        await api.createItem('TaskTemplate', {
            name: empty_template_name,
            content: '',
            entities_id: getWorkerEntityId(),
        });

        await api.createItem('PendingReason', {
            name: pending_reason_name,
            comment: 'For task e2e testing',
            entities_id: getWorkerEntityId(),
        });

        await api.createItem('TaskTemplate', {
            name: with_content_template_name,
            content: '<p>Task template test content</p>',
            entities_id: getWorkerEntityId(),
        });

        return {
            test_ticket_id,
            empty_template_name,
            pending_reason_name,
            with_content_template_name,
        };
    };

    const doOpenTaskForm = async (page: Page): Promise<Locator> => {
        // This button and this link embed an icon, their accessible name can
        // not be matched exactly.
        await page.getByRole('button', { name: 'View other actions' }).click();
        await page.getByRole('link', { name: 'Create a task' }).click();

        // eslint-disable-next-line playwright/no-raw-locators
        const task = page.locator('.itiltask').first();
        await expect(task).toBeVisible();

        return task;
    };

    const getTaskDropdown = (task: Locator, name: string): Locator => {
        // eslint-disable-next-line playwright/no-raw-locators
        return task.locator(`select[name="${name}"]`)
            .locator('+ span')
            .getByRole('combobox')
        ;
    };

    const getTaskContent = (task: Locator): Locator => {
        // eslint-disable-next-line playwright/no-raw-locators
        return task.locator('.tox-edit-area iframe')
            .contentFrame()
            .locator('body')
        ;
    };

    // Applying a template is done through an ajax request, wait for it instead
    // of relying on `cy.waitForNetworkIdle(500)`.
    const doApplyTaskTemplate = async (
        page: Page,
        glpi_page: GlpiPage,
        task: Locator,
        template_name: string,
    ): Promise<void> => {
        const response = page.waitForResponse('**/ajax/task.php');
        await glpi_page.doSearchAndClickDropdownValue(
            getTaskDropdown(task, 'tasktemplates_id'),
            template_name
        );
        await response;
    };

    test("preserves user's pending reason when applying template without pending reason", async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const fixtures = await setupFixtures(api);
        await page.goto(`/front/ticket.form.php?id=${fixtures.test_ticket_id}`);

        const task = await doOpenTaskForm(page);

        // eslint-disable-next-line playwright/no-raw-locators
        await task.locator('input[name="pending"][type="checkbox"]').check();

        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('[id^="pending-reasons-setup-"]'))
            .toBeVisible()
        ;

        const pending_reason_dropdown = getTaskDropdown(
            task,
            'pendingreasons_id'
        );
        await glpi_page.doSearchAndClickDropdownValue(
            pending_reason_dropdown,
            fixtures.pending_reason_name
        );
        await expect(pending_reason_dropdown)
            .toContainText(fixtures.pending_reason_name)
        ;

        await doApplyTaskTemplate(
            page,
            glpi_page,
            task,
            fixtures.empty_template_name
        );

        await expect(pending_reason_dropdown)
            .toContainText(fixtures.pending_reason_name)
        ;

        // eslint-disable-next-line playwright/no-raw-locators
        await expect(task.locator('input[name="pending"][type="checkbox"]'))
            .toBeChecked()
        ;
    });

    test("preserves user's content when applying template without content", async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const fixtures = await setupFixtures(api);
        await page.goto(`/front/ticket.form.php?id=${fixtures.test_ticket_id}`);

        const task = await doOpenTaskForm(page);

        const user_content = 'User typed task content';
        await getTaskContent(task).fill(user_content);
        await expect(getTaskContent(task)).toContainText(user_content);

        await doApplyTaskTemplate(
            page,
            glpi_page,
            task,
            fixtures.empty_template_name
        );

        await expect(getTaskContent(task)).toContainText(user_content);
    });

    test("replaces user's content when applying template with content", async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const fixtures = await setupFixtures(api);
        await page.goto(`/front/ticket.form.php?id=${fixtures.test_ticket_id}`);

        const task = await doOpenTaskForm(page);

        const user_content = 'User initial task content';
        await getTaskContent(task).fill(user_content);
        await expect(getTaskContent(task)).toContainText(user_content);

        const template_content = 'Task template test content';
        await doApplyTaskTemplate(
            page,
            glpi_page,
            task,
            fixtures.with_content_template_name
        );

        await expect(getTaskContent(task)).toContainText(template_content);
        await expect(getTaskContent(task)).not.toContainText(user_content);
    });
});
