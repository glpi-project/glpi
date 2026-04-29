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

import { Locator, Page } from "@playwright/test";
import { GlpiPage } from "./GlpiPage";

export class ImpactPage extends GlpiPage
{
    public readonly graph_view: Locator;
    public readonly list_view: Locator;
    public readonly canvas_elements: Locator;
    public readonly view_as_list_button: Locator;
    public readonly view_as_graph_button: Locator;

    public constructor(page: Page)
    {
        super(page);

        this.graph_view = page.getByTestId('impact-graph-view');
        this.list_view = page.getByTestId('impact-list-view');
        // eslint-disable-next-line playwright/no-raw-locators
        this.canvas_elements = page.locator('.__________cytoscape_container > canvas');
        this.view_as_list_button = page.getByTitle('View as list');
        this.view_as_graph_button = page.getByTitle('View graphical representation');
    }

    public async gotoComputerImpact(computer_id: number): Promise<void>
    {
        await this.page.goto(`/front/computer.form.php?id=${computer_id}&forcetab=Impact$1`);
    }
}
