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
    }

    async #filterItems()
    {
        const input = this.#getFilterInput();
        const url_params = new URLSearchParams({
            filter: input.value,
        });
        this.#loadItems(url_params);
    }

    async #loadChildren(e)
    {
        e.preventDefault();

        // Clear search filter
        const search_input = this.#getFilterInput();
        search_input.value = '';

        const element = e.currentTarget;
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

        // Reattach event listeners to newly loaded elements
        document
            .querySelectorAll('[data-children-url-parameters]')
            .forEach((composite) => composite.addEventListener(
                'click',
                (e) => this.#loadChildren(e)
            ));
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
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.breadcrumb = this.breadcrumb.slice(0, index + 1);
                    this.#updateBreadcrumb();
                    this.#loadItems(item.params);
                    history.pushState(
                        {
                            url_params: item.params,
                            breadcrumb: this.breadcrumb
                        },
                        '',
                        window.location.pathname
                    );
                });
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
}
