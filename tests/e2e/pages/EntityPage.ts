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
import { EntityPageTabs } from "./EntityPageTabs";

export class EntityPage extends GlpiPage
{
    // Form locators
    public readonly name_input: Locator;
    public readonly add_button: Locator;
    public readonly save_button: Locator;

    // Assistance tab locators
    public readonly show_tickets_on_helpdesk_dropdown: Locator;

    // Survey configuration locators
    public readonly survey_tickets_region: Locator;
    public readonly survey_config_dropdown: Locator;
    public readonly create_after_dropdown: Locator;
    public readonly rate_dropdown: Locator;
    public readonly duration_dropdown: Locator;
    public readonly max_rate_dropdown: Locator;
    public readonly default_rate_input: Locator;
    public readonly comment_required_input: Locator;
    public readonly closed_after_text: Locator;
    public readonly valid_tags_text: Locator;
    public readonly url_input: Locator;

    public constructor(page: Page)
    {
        super(page);

        // Form locators
        this.name_input = this.getTextbox('Name');
        this.add_button = this.getButton('Add');
        this.save_button = this.getButton('Save');

        // Assistance tab locators
        this.show_tickets_on_helpdesk_dropdown = this.getDropdownByLabel(
            'Show tickets properties on helpdesk'
        );

        // Survey configuration locators
        this.survey_tickets_region = this.page.getByLabel(
            'Configuring the satisfaction survey: Tickets'
        );
        this.survey_config_dropdown = this.getDropdownByLabel(
            'Configuring the satisfaction survey',
            this.survey_tickets_region
        );
        this.create_after_dropdown = this.getDropdownByLabel(
            'Create survey after',
            this.survey_tickets_region
        );
        this.rate_dropdown = this.getDropdownByLabel(
            'Rate to trigger survey',
            this.survey_tickets_region
        );
        this.duration_dropdown = this.getDropdownByLabel(
            'Duration of survey',
            this.survey_tickets_region
        );
        this.max_rate_dropdown = this.getDropdownByLabel(
            'Max rate',
            this.survey_tickets_region
        );
        this.default_rate_input = this.survey_tickets_region.getByLabel('Default rate');
        this.comment_required_input = this.survey_tickets_region.getByLabel(
            'Comment required if score is <= to'
        );
        this.closed_after_text = this.survey_tickets_region.getByText('For Tickets closed after');
        this.valid_tags_text = this.survey_tickets_region.getByText('Valid tags');
        this.url_input = this.survey_tickets_region.getByLabel('URL');
    }

    public async goto(id: number, tab: EntityPageTabs): Promise<void>
    {
        await this.page.goto(`/front/entity.form.php?id=${id}&forcetab=${tab}`);
    }

    public async gotoCreationPage(): Promise<void>
    {
        await this.page.goto('/front/entity.form.php');
    }

    public async gotoListingPage(): Promise<void>
    {
        await this.page.goto('/front/entity.php');
    }

    public async doCreateEntity(name: string): Promise<void>
    {
        await this.name_input.fill(name);
        await this.add_button.click();
        await expect(
            this.page.getByText("Item successfully added: ")
        ).toBeAttached();
    }
}
