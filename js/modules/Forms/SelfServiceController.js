/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/* global _ */

export class GlpiFormSelfServiceController
{
    constructor()
    {
        const input = this.#getFilterInput();
        const filterFormsDebounced = _.debounce(
            this.#filterForms.bind(this), // .bind keep the correct "this" context
            400,
            false
        );
        input.addEventListener('input', filterFormsDebounced);
    }

    async #filterForms()
    {
        const input = this.#getFilterInput();
        const url = `${CFG_GLPI.root_doc}/Forms`;
        const url_params = new URLSearchParams({
            filter: input.value,
        });
        const response = await fetch(`${url}?${url_params}`);
        this.#getFormsArea().innerHTML = await response.text();
    }

    #getFilterInput()
    {
        return document.querySelector("[data-glpi-service-catalog-filter-forms]");
    }

    #getFormsArea()
    {
        return document.querySelector("[data-glpi-service-catalog-forms]");
    }
}
