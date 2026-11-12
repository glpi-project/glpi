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
import { UserPage } from '../../pages/UserPage';
import { ComputerPage } from '../../pages/ComputerPage';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe('Tabs', () => {
    test('Can use the "forcetab" URL parameter to land on a specific tab', async ({
        page,
        profile,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const user_page = new UserPage(page);

        await user_page.gotoUserForm(2, 'Change$1');
        await expect(user_page.getTab('Created changes')).toHaveAttribute(
            'aria-selected',
            'true',
        );
        await expect(user_page.getTab('Created problems')).not.toHaveAttribute(
            'aria-selected',
            'true',
        );

        await user_page.gotoUserForm(2, 'Problem$1');
        await expect(user_page.getTab('Created problems')).toHaveAttribute(
            'aria-selected',
            'true',
        );
        await expect(user_page.getTab('Created changes')).not.toHaveAttribute(
            'aria-selected',
            'true',
        );
    });

    test('Displays an error when the tab content cannot be accessed', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const computer_id = await api.createItem('Computer', {
            name: 'Computer for tab access test',
            entities_id: getWorkerEntityId(),
        });
        const computer_page = new ComputerPage(page);
        await computer_page.goto(computer_id);

        // Lose the rights on the computer while its page is displayed
        await profile.set(Profiles.SelfService);

        await page.getByRole('tab', { name: /^Historical/ }).click();
        await expect(
            page.getByRole('tabpanel').getByRole('alert')
        ).toHaveText("You don't have permission to perform this action.");
    });
});
