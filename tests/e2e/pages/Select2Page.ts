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

import { expect, Locator, Page } from "@playwright/test";
import { GlpiPage } from "./GlpiPage";

/**
 * Keyboard and screen reader interactions with Select2 dropdowns.
 */
export class Select2Page extends GlpiPage
{
    public readonly focused_element: Locator;
    public readonly options: Locator;
    public readonly loading_option: Locator;
    public readonly dropdown_search_field: Locator;

    public constructor(page: Page)
    {
        super(page);

        // The element that screen readers follow, there is no semantic locator for it
        // eslint-disable-next-line playwright/no-raw-locators
        this.focused_element = page.locator(':focus');
        this.options = page.getByRole('listbox').getByRole('option');
        this.loading_option = page.getByRole('option', { name: 'Searching…' });
        // The search field is still in the DOM (and in the accessibility tree) when Select2 hides it,
        // so it can only be told apart from the other search fields of the page by its classes.
        // eslint-disable-next-line playwright/no-raw-locators
        this.dropdown_search_field = page.locator('.select2-container--open .select2-search--dropdown .select2-search__field');
    }

    public async doOpenDropdownWithKeyboard(dropdown: Locator): Promise<void>
    {
        await dropdown.focus();
        await this.page.keyboard.press('Enter');
        await expect(this.options.first()).toBeVisible();
        await expect(this.loading_option).toBeHidden();
    }

    public async doHighlightNextOption(): Promise<void>
    {
        await this.page.keyboard.press('ArrowDown');
    }

    /**
     * Screen readers announce the option referenced by the `aria-activedescendant`
     * attribute of the focused element.
     *
     * @returns the id of the announced option
     */
    public async assertHighlightedOptionIsAnnounced(previous_id: string | null = null): Promise<string>
    {
        await expect(this.focused_element).toHaveAttribute('aria-activedescendant', /.+/);
        const active_id = await this.focused_element.getAttribute('aria-activedescendant') ?? '';
        expect(active_id).not.toBe(previous_id);

        // eslint-disable-next-line playwright/no-raw-locators
        const active_option = this.page.locator(`[id="${active_id}"]`);
        await expect(active_option).toHaveRole('option');
        await expect(active_option).toHaveClass(/select2-results__option--highlighted/);

        return active_id;
    }
}
