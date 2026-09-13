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
import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';

test.describe('External event', () => {
    test('Accessibility - Form', async ({ page, profile }) => {
        await profile.set(Profiles.SuperAdmin);

        await page.goto('/front/planning.php');

        // Wait for the animations to be over
        await page.waitForFunction(() =>
            document.getAnimations().filter(a => a.playState === 'running').length === 0
        );

        // eslint-disable-next-line playwright/no-raw-locators
        await page.locator('#planning_container .fc-slats td:not(.fc-axis)')
            .first()
            .click()
        ;

        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('.modal.show')).toBeVisible();

        const modal_a11y = await new AxeBuilder({ page })
            .include('.modal.show')
            // Known issues inherited from 3rd party libraries, taken from the
            // original exclusion list of the cypress `injectAndCheckA11y`
            // command.
            .exclude('.tox-tinymce') // TinyMCE
            .exclude('.select2-container') // Select2
            .exclude('div.fileupload') // JQuery File upload
            .exclude('.flatpickr input[type="text"]') // Flatpickr altInput
            .exclude('*[data-hasqtip]') // Popper tooltips used as labels
            .analyze()
        ;
        expect(modal_a11y.violations).toEqual([]);
    });
});
