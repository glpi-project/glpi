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

/* global _, setupAdaptDropdown */

export class GlpiFormServiceCatalogController
{
    /**
     * @constructor
     * @param {Object} sort_icons - Icons for sorting
     */
    constructor(sort_icons)
    {
        this.breadcrumb = [];
        this.sort_icons = sort_icons;

        const input = this.#getFilterInput();
        const filterFormsDebounced = _.debounce(
            this.#filterItems.bind(this), // .bind keep the correct "this" context
            400,
            false
        );
        input.addEventListener('input', filterFormsDebounced);
        // Handle page load with URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.size > 0) {
            this.#loadItems(urlParams.toString());
        }

        // Handle back/forward navigation
        window.addEventListener('popstate', (event) => {
            if (event.state && event.state.url_params) {
                this.#loadItems(event.state.url_params);
                if (event.state.breadcrumb) {
                    this.breadcrumb = event.state.breadcrumb;
                }
            }
        });

        // Handle composite, breadcrumb and pagination clicks
        document.addEventListener('click', (e) => {
            const compositeItem = $(e.target).closest('[data-composite-item]');
            if (compositeItem.length === 1) {
                // Prevent loading the same page again
                e.preventDefault();

                this.#loadChildren(compositeItem.get(0));
            }

            const breadcrumbItem = $(e.target).closest('[data-breadcrumb-item]');
            if (breadcrumbItem.length === 1) {
                // Prevent loading the same page again
                e.preventDefault();

                const index = this.breadcrumb.findIndex(item => item.params === breadcrumbItem.data('childrenUrlParameters'));
                this.breadcrumb = this.breadcrumb.slice(0, index + 1);
                this.#loadItems(breadcrumbItem.data('childrenUrlParameters'));
                this.#updateHistory(breadcrumbItem.data('childrenUrlParameters'));
            }

            const pageLink = $(e.target).closest('[data-pagination-item]');
            if (pageLink.length === 1 && !pageLink.hasClass('disabled')) {
                // Prevent loading the same page again
                e.preventDefault();

                this.#loadPage(pageLink.get(0));
            }
        });

        // Initialize the sort select after the DOM is ready
        const sortSelect = document.querySelector('[data-glpi-service-catalog-sort-strategy]');
        setTimeout(() => {
            setupAdaptDropdown(window.select2_configs[sortSelect.id])
                .on('select2:select', (e) => {
                    const sort_strategy = e.params.data.id;
                    this.#applySortStrategy(sort_strategy);
                });
        }, 0);
    }

    async #filterItems()
    {
        const input = this.#getFilterInput();
        const url_params = new URLSearchParams({
            filter: input.value,
            page: 1, // Reset to first page when filtering
        });
        this.#loadItems(url_params);
    }

    async #loadChildren(element)
    {
        // Clear search filter
        const search_input = this.#getFilterInput();
        search_input.value = '';

        const url_params = element.dataset['childrenUrlParameters'];

        // Get children items from backend
        this.#loadItems(url_params);
        this.#updateHistory(url_params);
    }

    async #loadItems(url_params)
    {
        const url = `${CFG_GLPI.root_doc}/ServiceCatalog/Items`;
        let response = await fetch(`${url}?${url_params}`);
        if (!response.ok) { // We fallback the response to the root page
            response = await fetch(`${url}`);
            this.#updateHistory('');
        }

        this.#getFormsArea().innerHTML = await response.text();
        this.#updateBreadcrumb();
    }

    async #loadPage(element) {
        const url_params = element.dataset.childrenUrlParameters;

        // Get children items from backend
        this.#loadItems(url_params);

        // Push state to history with breadcrumb
        this.#updateHistory(url_params);
    }

    async #applySortStrategy(sort_strategy) {
        const url_params = new URLSearchParams();
        url_params.set('sort_strategy', sort_strategy);
        url_params.set('filter', this.#getFilterInput().value); // Keep the current filter
        url_params.set('page', 1); // Reset to first page when sorting
        this.#loadItems(url_params);
    }

    #updateBreadcrumb() {
        const categoryAncestors = document.querySelector('#category-ancestors');
        if (categoryAncestors) {
            this.breadcrumb = [{
                title: __('Service catalog'),
                params: 'category=0'
            }];

            const ancestors = JSON.parse(categoryAncestors.dataset.ancestors);
            ancestors.forEach(ancestor => {
                this.breadcrumb.push({
                    title: ancestor.name,
                    params: `category=${ancestor.id}`
                });
            });
        }

        const breadcrumbContainer = document.querySelector('.breadcrumb');
        breadcrumbContainer.innerHTML = '';

        this.breadcrumb.forEach((item, index) => {
            const li = document.createElement('li');
            li.className = 'breadcrumb-item text-truncate';
            if (index === this.breadcrumb.length - 1) {
                li.classList.add('active');
            }

            const a = document.createElement('a');
            a.href = `?${item.params}`;
            a.textContent = item.title;
            if (index < this.breadcrumb.length - 1) {
                a.dataset.childrenUrlParameters = item.params;
                a.dataset.breadcrumbItem = '';
            }

            li.appendChild(a);
            breadcrumbContainer.appendChild(li);
        });
    }

    #updateHistory(url_params)
    {
        const location = new URL(window.location.href);
        location.search = '';

        const params = new URLSearchParams(url_params);
        params.forEach((value, key) => {
            if (key === 'category' && value === '0') {
                return;
            }

            location.searchParams.set(key, value);
        });
        // Push state to history with breadcrumb
        history.pushState(
            {
                url_params,
                breadcrumb: this.breadcrumb
            },
            '',
            location
        );
    }

    #getFilterInput()
    {
        return document.querySelector("[data-glpi-service-catalog-filter-items]");
    }

    #getFormsArea()
    {
        return document.querySelector("[data-glpi-service-catalog-items]");
    }

    getTemplateForSortSelect(data) {
        const icon = this.sort_icons[data.id];
        return $(`<span class="w-full" title="${_.escape(data.text)}" aria-label="${_.escape(data.text)}"><i class="${_.escape(icon)}"></i></span>`);
    }
}
