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
import { LogViewerPage } from '../../pages/LogViewerPage';
import { Profiles } from '../../utils/Profiles';
import AxeBuilder from '@axe-core/playwright';

test('Log list has items', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const log_viewer_page = new LogViewerPage(page);
    await log_viewer_page.gotoLogList();
    await expect(log_viewer_page.log_list_items.first()).toBeVisible();

    const a11y_results = await new AxeBuilder({ page })
        .include('[data-testid="log-list-item"]')
        .analyze()
    ;
    expect(a11y_results.violations).toEqual([]);
});

test('Log viewer has entries', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const log_viewer_page = new LogViewerPage(page);
    await log_viewer_page.gotoLogViewer('php-errors.log');
    await expect(log_viewer_page.log_entries.first()).toBeVisible();

    const a11y_results = await new AxeBuilder({ page })
        .include('[data-testid="log-entries"]')
        .analyze()
    ;
    expect(a11y_results.violations).toEqual([]);
});
