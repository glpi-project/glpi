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

import { post } from '/js/modules/Ajax.js';

const show_label = __('Show advanced options');
const hide_label = __('Hide advanced options');

export class GlpiKnowbasePermissionsFormController
{
    /**
     * @type {object}
     */
    #visibilityDropdownParams;

    /**
     * @type {HTMLElement}
     */
    #dropdown;

    /**
     * @type {HTMLElement}
     */
    #visibility;

    /**
     * @type {HTMLElement}
     */
    #visibilityRecursive;

    /**
     * @type {HTMLElement}
     */
    #submitBtn;

    /**
     * @type {HTMLElement}
     */
    #advancedToggle;

    /**
     * @type {HTMLElement}
     */
    #advancedBlock;

    /**
     * @type {HTMLElement}
     */
    #advancedContent;

    /**
     * @type {HTMLElement}
     */
    #advancedVisible = false;

    /**
     * @param {HTMLElement} container
     * @param {string} rand
     * @param {object} visibilityDropdownParams
     */
    constructor(container, rand, visibilityDropdownParams)
    {
        this.#visibilityDropdownParams = visibilityDropdownParams;

        this.#dropdown            = document.getElementById(`dropdown__type${rand}`);
        this.#visibility          = document.getElementById(`visibility${rand}`);
        this.#visibilityRecursive = document.getElementById(`visibility-recursive${rand}`);
        this.#submitBtn           = document.getElementById(`visibility-submit-btn${rand}`);
        this.#advancedToggle      = document.getElementById(`visibility-advanced-toggle${rand}`);
        this.#advancedBlock       = document.getElementById(`visibility-advanced${rand}`);
        this.#advancedContent     = document.getElementById(`visibility-advanced-content${rand}`);

        this.#bindEvents();
    }

    #resetAdvanced()
    {
        this.#advancedVisible = false;
        this.#advancedBlock.classList.add('d-none');
        this.#advancedContent.innerHTML = '';
        this.#advancedToggle.innerHTML = `<i class="ti ti-settings me-1"></i>${show_label}`;
    }

    async #loadVisibility(type)
    {
        const params = new URLSearchParams({
            ...this.#visibilityDropdownParams,
            type,
            nobutton: 1,
        });
        const response = await post('ajax/visibility.php', params);
        const html = await response.text();
        $(this.#visibility).html(html);
    }

    async #loadSubvisibility(type)
    {
        const params = new URLSearchParams({ type });
        const response = await post('ajax/subvisibility.php', params);
        const html = await response.text();
        $(this.#advancedContent).html(html); // jQuery is needed here to run scripts
    }

    #bindEvents()
    {
        // Handle itemtype changes
        // Note: select2 events only work with jQuery handlers
        $(this.#dropdown).on('change', async (e) => {
            this.#resetAdvanced();

            // No type selected: hide item dropdown
            if (e.target.value === '') {
                this.#visibility.innerHTML = '';
                this.#visibility.classList.add('d-none');
                this.#visibilityRecursive.classList.add('d-none');
                this.#submitBtn.classList.add('d-none');
                this.#advancedToggle.classList.add('d-none');
                return;
            }

            const type = e.target.value;

            await this.#loadVisibility(type);
            this.#visibility.classList.remove('d-none');
            this.#submitBtn.classList.remove('d-none');

            if (type === 'Entity') {
                // Specific layout for entities (child entities dropdown)
                this.#visibilityRecursive.classList.remove('d-none');
                this.#advancedToggle.classList.add('d-none');
            } else {
                // Groups and profiles have access to advanced options
                this.#visibilityRecursive.classList.add('d-none');

                if (type === 'Group' || type === 'Profile') {
                    this.#advancedToggle.classList.remove('d-none');
                } else {
                    this.#advancedToggle.classList.add('d-none');
                }
            }
        });

        // Handle advanced options toggle
        this.#advancedToggle.addEventListener('click', async () => {
            this.#advancedVisible = !this.#advancedVisible;

            if (this.#advancedVisible) {
                // Show advanced options
                this.#advancedBlock.classList.remove('d-none');
                await this.#loadSubvisibility(this.#dropdown.value);
                const html = `<i class="ti ti-settings me-1"></i>${hide_label}`;
                this.#advancedToggle.innerHTML = html;
            } else {
                // Hide advanced options
                this.#advancedBlock.classList.add('d-none');
                this.#advancedContent.innerHTML = '';
                const html = `<i class="ti ti-settings me-1"></i>${show_label}`;
                this.#advancedToggle.innerHTML = html;
            }
        });
    }
}
