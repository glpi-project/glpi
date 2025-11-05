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
import { GlpiFormDestinationAutoConfigController } from "./DestinationAutoConfigController.js";
import { GlpiFormDestinationConditionController } from "./DestinationConditionController.js";

export class GlpiFormDestinationAccordionController
{
    constructor() {
        this.#watchForAccordionToggle();
    }

    triggerWatchers() {
        new GlpiFormDestinationAutoConfigController();
        new GlpiFormDestinationConditionController();
    }

    #watchForAccordionToggle() {
        const accordionWrapper = document.querySelector('#glpi-destinations-accordion');

        accordionWrapper.addEventListener('show.bs.collapse', async (e) => {
            const accordionItem = e.target;
            const accordionItemContent = accordionItem.querySelector('.accordion-body');
            if (accordionItemContent.innerHTML.trim() !== '') {
                return;
            }

            accordionItemContent.innerHTML = '<div class="text-center"><div class="spinner-border text-primary mb-3" role="status"></div></div>';

            const content = await $.ajax({
                url: `${CFG_GLPI.root_doc}/Form/${accordionItem.dataset.form}/Destinations/${accordionItem.dataset.formDestination}`,
                method: 'GET',
            });

            // Note: must use `$().html` to make sure we trigger scripts
            $(accordionItemContent).html(content);

            // We trigger the watcher
            this.triggerWatchers();
        });
    }
}
