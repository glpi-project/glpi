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
});
