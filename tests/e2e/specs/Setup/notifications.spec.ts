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
import { Profiles } from '../../utils/Profiles';
import { NotificationPage } from '../../pages/NotificationPage';
import { NotificationTemplatePage } from '../../pages/NotificationTemplatePage';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('View Templates for a Notification', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    const template_id = await api.createItem('NotificationTemplate', {
        'name': 'Test Notification Template',
        'itemtype': 'Ticket',
    });

    const notification_id = await api.createItem('Notification', {
        'name': 'Test Notification',
        'itemtype': 'Ticket',
        'event': 'new',
        'is_active': 1,
        'entities_id': getWorkerEntityId(),
    });

    await api.createItem('Notification_NotificationTemplate', {
        'notifications_id': notification_id,
        'notificationtemplates_id': template_id,
        'mode': 'mailing',
    });

    const notification_page = new NotificationPage(page);
    await notification_page.goto(notification_id, 'Notification_NotificationTemplate$1');

    // Verify the Templates tab is active
    await expect(page.getByRole('tab', { name: 'Templates', exact: true })).toHaveAttribute(
        'aria-selected',
        'true'
    );

    const tabpanel = page.getByRole('tabpanel');

    // Verify "Add a template" button is present
    await expect(tabpanel.getByRole('button', { name: 'Add a template' })).toBeVisible();

    // Verify table column headers and structure
    const table = tabpanel.getByRole('table');
    await expect(table.getByRole('columnheader', { name: 'ID', exact: true })).toBeVisible();
    await expect(table.getByRole('columnheader', { name: 'Template', exact: true })).toBeVisible();
    await expect(table.getByRole('columnheader', { name: 'Mode', exact: true })).toBeVisible();

    // Verify the select-all checkbox in the header
    await expect(table.getByRole('checkbox', { name: 'Check all' })).toBeVisible();

    // Verify the ID column links to the notification_notificationtemplate form
    await expect(table.getByRole('link', { name: /^\d+$/ }).first()).toHaveAttribute(
        'href',
        /notification_notificationtemplate\.form\.php/
    );

    // Verify the Template column links to the notificationtemplate form
    await expect(table.getByRole('link', { name: 'Test Notification Template' })).toHaveAttribute(
        'href',
        /notificationtemplate\.form\.php/
    );

    // Click "Add a template" and verify the redirect
    await tabpanel.getByRole('button', { name: 'Add a template' }).click();
    await expect(page).toHaveURL(/notification_notificationtemplate\.form\.php/);
    await expect(page).toHaveURL(new RegExp(`notifications_id=${notification_id}`));

    // Verify the back-link to the notification
    await expect(page.getByRole('link', { name: 'Test Notification' })).toHaveAttribute(
        'href',
        new RegExp(`/front/notification\\.form\\.php.*id=${notification_id}`)
    );

    // Navigate back to the Templates tab
    await notification_page.goto(notification_id, 'Notification_NotificationTemplate$1');

    // Click the template name link to open the notification template form
    await page.getByRole('tabpanel').getByRole('link', { name: 'Test Notification Template' }).click();

    // Navigate to the Template translations tab on the notification template form
    const template_page = new NotificationTemplatePage(page);
    await template_page.goto(template_id, 'NotificationTemplateTranslation$1');

    // Verify "Add a new translation" link is present
    await expect(page.getByRole('link', { name: 'Add a new translation' })).toBeVisible();

    // Click the link and verify the redirect
    await page.getByRole('link', { name: 'Add a new translation' }).click();
    await expect(page).toHaveURL(/notificationtemplatetranslation\.form\.php/);

    // Fill the subject and submit
    await page.getByRole('textbox', { name: 'Subject' }).fill('Test Subject');
    await page.getByRole('button', { name: 'Add' }).click();

    // Verify the toast contains a "Default translation" link
    await expect(
        page.getByRole('alert').getByRole('link', { name: 'Default translation' })
    ).toBeVisible();
});
