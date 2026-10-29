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
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('Skip link to search results', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.goto('/front/computer.php');

    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Go to main content' })).toBeFocused();
    await page.keyboard.press('Tab');
    const skip_link = page.getByRole('link', { name: 'Go to search results' });
    await expect(skip_link).toBeFocused();
    await expect(page.getByRole('link', { name: 'Go to timeline', includeHidden: true })).toBeHidden();

    await page.keyboard.press('Enter');
    await expect(page.getByTestId('search-container')).toBeFocused();
});

test('Skip link to ITIL timeline', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test ticket for timeline skip link',
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });
    await page.goto(`/front/ticket.form.php?id=${ticket_id}`);
    await expect(page.getByTestId('itil-timeline')).toBeAttached();

    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Go to main content' })).toBeFocused();
    await page.keyboard.press('Tab');
    const skip_link = page.getByRole('link', { name: 'Go to timeline' });
    await expect(skip_link).toBeFocused();
    await expect(page.getByRole('link', { name: 'Go to search results', includeHidden: true })).toBeHidden();

    await page.keyboard.press('Enter');
    await expect(page.getByTestId('itil-timeline')).toBeFocused();
});

test('Contextual skip links are not available on other pages', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    await page.goto('/front/central.php');

    await expect(page.getByRole('link', { name: 'Go to main content' })).toBeAttached();
    await expect(page.getByRole('link', { name: 'Go to search results', includeHidden: true })).toBeHidden();
    await expect(page.getByRole('link', { name: 'Go to timeline', includeHidden: true })).toBeHidden();
});
