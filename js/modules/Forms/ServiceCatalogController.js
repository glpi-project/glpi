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
        this.sort_icons = sort_icons;

        const input = this.#getFilterInput();
        const filterFormsDebounced = _.debounce(
            this.#filterItems.bind(this), // .bind keep the correct "this" context
            400,
            false
        );
        input.addEventListener('input', filterFormsDebounced);

        // Initialize breadcrumb with root level
        this.breadcrumb = [{
            title: __('Service catalog'),
            params: 'category=0'
        }];

        // Handle back/forward navigation
        window.addEventListener('popstate', (event) => {
            if (event.state && event.state.url_params) {
                this.#loadItems(event.state.url_params);
                if (event.state.breadcrumb) {
                    this.breadcrumb = event.state.breadcrumb;
                    this.#updateBreadcrumb();
                }
            }
        });

        // Push initial state to history
        history.replaceState(
            {
                url_params: 'category=0',
                breadcrumb: this.breadcrumb
            },
            '',
            window.location.pathname
        );

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
                this.#updateBreadcrumb();
                this.#loadItems(breadcrumbItem.data('childrenUrlParameters'));
                history.pushState(
                    {
                        url_params: breadcrumbItem.data('childrenUrlParameters'),
                        breadcrumb: this.breadcrumb
                    },
                    '',
                    window.location.pathname
                );
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
                    this.#loadSortStrategy(sort_strategy);
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

        const title = element.querySelector('.card-title') ? element.querySelector('.card-title').textContent : '';
        const url_params = element.dataset['childrenUrlParameters'];

        // Update breadcrumb if it doesn't already contain the current item
        if (!this.breadcrumb.some(item => item.params === url_params)) {
            this.breadcrumb.push({
                title: title,
                params: url_params
            });
            this.#updateBreadcrumb();
        }

        // Get children items from backend
        this.#loadItems(url_params);

        // Push state to history with breadcrumb
        history.pushState(
            {
                url_params,
                breadcrumb: this.breadcrumb
            },
            '',
            window.location.pathname
        );
    }

    async #loadItems(url_params)
    {
        const url = `${CFG_GLPI.root_doc}/ServiceCatalog/Items`;
        const response = await fetch(`${url}?${url_params}`);
        this.#getFormsArea().innerHTML = await response.text();
    }

    async #loadPage(element) {
        const url_params = element.dataset.childrenUrlParameters;

        // Get children items from backend
        this.#loadItems(url_params);

        // Push state to history with breadcrumb
        history.pushState(
            {
                url_params: url_params,
                breadcrumb: this.breadcrumb
            },
            '',
            window.location.pathname
        );
    }

    async #loadSortStrategy(sort_strategy) {
        const url_params = new URLSearchParams();
        url_params.set('sort_strategy', sort_strategy);
        url_params.set('filter', this.#getFilterInput().value); // Keep the current filter
        url_params.set('page', 1); // Reset to first page when sorting
        this.#loadItems(url_params);
    }

    #updateBreadcrumb() {
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
        return $(`<span class="d-block w-full" title="${data.text}" aria-label="${data.text}"><i class="${icon}"></i></span>`);
    }
}
