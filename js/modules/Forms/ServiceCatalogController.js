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

/* global _ */

export class GlpiFormServiceCatalogController
{
    constructor()
    {
        const input = this.#getFilterInput();
        const filterFormsDebounced = _.debounce(
            this.#filterItems.bind(this), // .bind keep the correct "this" context
            400,
            false
        );
        input.addEventListener('input', filterFormsDebounced);

        // Load children items when composite items are clicked
        document
            .querySelectorAll('[data-children-url-parameters]')
            .forEach((composite) => composite.addEventListener(
                'click',
                (e) => this.#loadChildren(e)
            ))
        ;
    }

    async #filterItems()
    {
        const input = this.#getFilterInput();
        const url = `${CFG_GLPI.root_doc}/ServiceCatalog/Items`;
        const url_params = new URLSearchParams({
            filter: input.value,
        });
        const response = await fetch(`${url}?${url_params}`);
        this.#getFormsArea().innerHTML = await response.text();
    }

    async #loadChildren(e)
    {
        e.preventDefault();

        // Clear search filter
        const search_input = this.#getFilterInput();
        search_input.value = '';

        // Get children items from backend
        const url = `${CFG_GLPI.root_doc}/ServiceCatalog/Items`;
        const url_params = e.currentTarget.dataset['childrenUrlParameters']; // ref to data-children-url-parameters
        const response = await fetch(`${url}?${url_params}`);
        this.#getFormsArea().innerHTML = await response.text();
    }

    #getFilterInput()
    {
        return document.querySelector("[data-glpi-service-catalog-filter-items]");
    }

    #getFormsArea()
    {
        return document.querySelector("[data-glpi-service-catalog-items]");
    }
}
