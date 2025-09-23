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

/* global bootstrap, _, getAjaxCsrfToken */

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

    /**
     * @type {HTMLElement}
     */
    #modal_node;

    /**
     * Const value forwarded from backend code
     * @type {string}
     */
    #custom_icon_prefix;

    constructor(container, modal_node, custom_icon_prefix)
    {
        this.#container = container;
        this.#modal_node = modal_node;
        this.#custom_icon_prefix = custom_icon_prefix;
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

            this.#setNativeIllustration(illustration);
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

        // Watch for custom file selection
        this.#container
            .querySelector('[data-glpi-icon-picker-use-custom-file]')
            .addEventListener("click", async () => {
                const file_id = await this.#uploadIcon();
                if (file_id !== null) {
                    this.#setCustomIllustration(file_id);
                }
                bootstrap.Modal.getInstance(this.#modal_node).hide();
            })
        ;

        /**
         * Sometimes the tab content is not visible when the modal opens.
         * This problem occurs especially when our modal is contained within a tab.
         * This solution forces the display of the active tab.
         */
        this.#modal_node.addEventListener('show.bs.modal', () => {
            const active_tab_id = this.#container.querySelector(".nav-link.active").dataset.bsTarget;
            this.#container.querySelector(active_tab_id).classList.add('show', 'active');
        });

        // Autofocus search input when the modal is opened
        this.#modal_node.addEventListener('shown.bs.modal', () => {
            this.#container.querySelector("[data-glpi-icon-picker-filter]").focus();
        });
    }

    #setNativeIllustration(illustration)
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

        this.#container
            .querySelector('[data-glpi-icon-picker-value-preview-native]')
            .classList
            .remove('d-none')
        ;
        this.#container
            .querySelector('[data-glpi-icon-picker-value-preview-custom]')
            .classList
            .add('d-none')
        ;
    }

    #setCustomIllustration(file_id)
    {
        const icon_id = `${this.#custom_icon_prefix}${file_id}`;
        this.#getSelectedIllustrationsInput().value = icon_id;

        // Update preview
        this.#container
            .querySelector('[data-glpi-icon-picker-value-preview-custom]')
            .querySelector('img')
            .src = `${CFG_GLPI.root_doc}/UI/Illustration/CustomIllustration/${file_id}`
        ;
        this.#container
            .querySelector('[data-glpi-icon-picker-value-preview-custom]')
            .classList
            .remove('d-none')
        ;
        this.#container
            .querySelector('[data-glpi-icon-picker-value-preview-native]')
            .classList
            .add('d-none')
        ;
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
            .querySelector(`[data-glpi-icon-picker-go-to-page="${CSS.escape(page)}"]`)
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

    async #uploadIcon()
    {
        // Multiple files may be uploaded despite calling Html::file()
        // with the `multiple = false` parameter.
        // We must thus look for the first input `_custom_icon[X]` input.
        // Note that it wont always be `_custom_icon[0]`, e.g. the user
        // can upload two files and delete the first one.
        const possible_inputs = this.#container.querySelectorAll(
            "input[name^='_custom_icon']"
        );
        let input = null;
        for (const possible_input of possible_inputs) {
            input = possible_input;
            break;
        }

        if (input === null) {
            return null;
        }

        // Save the icon on the server and get its path.
        const form_data = new FormData();
        form_data.append('filename', input.value);
        const url = `${CFG_GLPI.root_doc}/UI/Illustration/Upload`;
        const response = await fetch(url, {
            method: 'POST',
            body: form_data,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
            }
        });

        const data = await response.json();
        return data.file;
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
