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

/* global bootstrap, _ */

export class GlpiIllustrationPickerController
{
    /**
     * @type {HTMLElement}
     */
    #container;

    /**
     * @type {Number}
     */
    #running_search_requests_count = 0;

    constructor(container)
    {
        this.#container = container;
        this.#initEventListeners();
    }

    #initEventListeners()
    {
        // Here we must watch for events on the whole container as its content will
        // be dynamically updated when using search or pagination.

        // Watch for icon selection
        this.#container.addEventListener("click", (e) => {
            const illustration = e.target.closest('[data-glpi-icon-picker-value]');

            // Click must be on an illustration.
            if (illustration === null) {
                return;
            }

            this.#setIllustration(illustration);
        });

        // Watch for page change
        this.#container.addEventListener("click", (e) => {
            const go_to_button = e.target.closest('[data-glpi-icon-picker-go-to-page]');

            // Click must be on a "go to page..." button.
            if (go_to_button === null) {
                return;
            }

            const page = go_to_button.dataset['glpiIconPickerGoToPage'];
            this.#goToPage(page);
        });

        // Watch for filter change
        const debouncedSearch = _.debounce(
            (filter) => this.#filterIcons(filter),
            200,
        );
        this.#container.addEventListener("input", (e) => {
            const filter_input = e.target.closest('[data-glpi-icon-picker-filter]');

            // Input event must come from the filter input.
            if (filter_input === null) {
                return;
            }

            debouncedSearch(filter_input.value);
        });
    }

    #setIllustration(illustration)
    {
        // Gets details of the newly selected item.
        const illustration_id = illustration.dataset['glpiIconPickerValue'];
        const illustration_title = illustration
            .querySelector('svg')
            .querySelector('title')
        ;

        // Apply the new illustration id to the hidden input.
        this.#getSelectedIllustrationsInput().value = illustration_id;

        // Update the preview of the selected item.
        const selected_svg = this.#container
            .querySelector('[data-glpi-icon-picker-value-preview]')
            .querySelector('svg')
        ;
        const title = selected_svg.querySelector('title');
        const use = selected_svg.querySelector('use');
        const xlink = use.getAttribute('xlink:href');

        use.setAttribute(
            'xlink:href',
            xlink.replace(/#.*/, `#${illustration_id}`)
        );
        title.innerHTML = illustration_title.innerHTML;
    }

    /**
     * Note: a new search might be triggered while the previous one is still
     * running if the server is very slow.
     * Thus we must keep track of running requests using to make sure the
     * loading styles are only removed when the last request is finished.
     */
    async #filterIcons(filter = "")
    {
        // Apply loading styles.
        if (this.#running_search_requests_count == 0) {
            this.#getSearchDefaultIcon().classList.add('d-none');
            this.#getSearchLoadingIcon().classList.remove('d-none');
            this.#getSearchResultsDiv().style.opacity = 0.7;
            this.#getSearchResultsDiv().style['pointer-events'] = 'none';
        }
        this.#running_search_requests_count++;

        // Execute search (always go back to first page as results will change)
        await this.#fetchIcons(filter, 1);

        // Remove loading styles
        this.#running_search_requests_count--;
        if (this.#running_search_requests_count == 0) {
            this.#getSearchDefaultIcon().classList.remove('d-none');
            this.#getSearchLoadingIcon().classList.add('d-none');
            this.#getSearchResultsDiv().style.opacity = 1;
            this.#getSearchResultsDiv().style['pointer-events'] = null;
        }
    }

    async #goToPage(page)
    {
        // Remove active button
        this.#container
            .querySelectorAll('[data-glpi-icon-picker-go-to-page]')
            .forEach((button) => button.classList.remove('active'))
        ;

        // Apply loading indicator to the new active button
        const button = this.#container
            .querySelector(`[data-glpi-icon-picker-go-to-page="${page}"]`)
        ;

        button.classList.add('active');
        button.classList.add('btn-loading');

        // Apply loading indicator to the search results
        this.#getSearchResultsDiv().style.opacity = 0.7;
        this.#getSearchResultsDiv().style['pointer-events'] = 'none';

        await this.#fetchIcons(this.#getFilterInput().value, page);

        // Remove loading indicator from the search results.
        this.#getSearchResultsDiv().style.opacity = 1;
        this.#getSearchResultsDiv().style['pointer-events'] = null;

        // Note: the pagination will be refreshed with new content from the
        // server, thus we don't need to revert the style/classes changes to the
        // pagination buttons.
    }

    async #fetchIcons(filter = "", page = 1)
    {
        const url = `${CFG_GLPI.root_doc}/UI/Illustration/Search`;
        const url_params = new URLSearchParams({
            filter   : filter,
            page     : page,
            page_size: this.#getPageSizeValue(),
        });
        const response = await fetch(`${url}?${url_params}`);

        this.#getSearchResultsDiv().innerHTML = await response.text();
    }

    #getFilterInput()
    {
        return this.#container.querySelector('[data-glpi-icon-picker-filter]');
    }

    #getPageSizeValue()
    {
        return this.#container.querySelector('[data-glpi-icon-picker-page-size]')
            .dataset['glpiIconPickerPageSize']
        ;
    }

    #getSelectedIllustrationsInput()
    {
        return this.#container.querySelector('[data-glpi-icon-picker-value]');
    }

    #getSearchDefaultIcon()
    {
        return this.#container.querySelector('[data-glpi-icon-picker-filter-default-icon]')
        ;
    }

    #getSearchLoadingIcon()
    {
        return this.#container.querySelector('[data-glpi-icon-picker-filter-loading-icon]');
    }

    #getSearchResultsDiv()
    {
        return this.#container.querySelector('[data-glpi-icon-picker-body]');
    }
}
