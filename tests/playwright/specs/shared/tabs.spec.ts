/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

import { test, expect } from '../../fixtures/authenticated';
import { CommonDBTMPage } from '../../pages/CommonDBTMPage';
import { SessionManager } from '../../utils/SessionManager';

test('can use the "forcetab" URL parameter', async ({ page, request }) => {
    // Load super admin profile
    const session = new SessionManager(request);
    await session.changeProfile("Super-Admin");

    // Load the glpi's user details and force the "Change" tab
    const user_page = new CommonDBTMPage(page);
    page.goto("/front/user.form.php?id=2&forcetab=Change_Item$1");
    await expect.soft(user_page.getTab('Changes')).toHaveAttribute(
        'aria-selected', 'true'
    );
    await expect.soft(user_page.getTab('Problems')).toHaveAttribute(
        'aria-selected', 'false'
    );

    // Force the "Problem" tab
    page.goto("/front/user.form.php?id=2&forcetab=Item_Problem$1");
    await expect(user_page.getTab('Changes')).toHaveAttribute(
        'aria-selected', 'false'
    );
    await expect(user_page.getTab('Problems')).toHaveAttribute(
        'aria-selected', 'true'
    );
});

