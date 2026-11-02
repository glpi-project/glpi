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

test.describe('Kanban', () => {
    test('Global project kanban loads', async ({ page, profile }) => {
        await profile.set(Profiles.SuperAdmin);

        await page.goto('/front/project.form.php?showglobalkanban=1');

        //TODO I am well aware this test is not using the "accessible" selectors, but that isn't the point.
        // I am aware that the Kanban needs an accessibility review, but I will do that as a separate task with user experience in mind,
        // not just blindly adding labels, roles or data attributes to everything just to make the test look "nicer".
        /* eslint-disable playwright/no-raw-locators */
        const kanban = page.locator('#kanban-app');

        // The loading spinner should disappear when the kanban is loaded
        await expect(kanban.locator('.kanban-container')).toBeVisible();
        await expect(kanban.getByRole('status')).not.toBeAttached();

        const toolbar = kanban.locator('.kanban-toolbar');
        await expect(toolbar.locator('select[name="kanban-board-switcher"]'))
            .toHaveValue('-1')
        ;
        await expect(toolbar.locator('div.search-input')).toBeVisible();
        await expect(toolbar.getByRole('button', { name: 'Add column' }))
            .toBeVisible()
        ;
        await expect(toolbar.locator('button.kanban-extra-toolbar-options'))
            .toBeVisible()
        ;

        await toolbar.locator('.search-input-tag-input').click();
        const popover = toolbar.locator('.search-input-popover');
        await expect(popover).toBeVisible();

        const expected_tags = [
            'title', 'type', 'milestone', 'content', 'deleted', 'team', 'user', 'group', 'supplier', 'contact'
        ];
        for (const tag of expected_tags) {
            const tag_item = popover.locator(`li[data-tag="${tag}"]`);
            await expect(tag_item.locator('b')).toContainText(tag);

            const exclude_button = tag_item.getByRole('button', { name: '!' });
            await expect(exclude_button).toBeVisible();
            await expect(exclude_button).toHaveAttribute('title', 'Exclude');

            // Only the free text tags can be searched with a regex
            // eslint-disable-next-line playwright/no-conditional-in-test
            if (['title', 'content'].includes(tag)) {
                /* eslint-disable playwright/no-conditional-expect */
                const regex_button = tag_item.getByRole('button', { name: '#' });
                await expect(regex_button).toBeVisible();
                await expect(regex_button).toHaveAttribute('title', 'Regex');
                /* eslint-enable playwright/no-conditional-expect */
            }

            await expect(tag_item.locator('.text-muted')).not.toBeEmpty();
        }
        /* eslint-enable playwright/no-raw-locators */
    });
});
