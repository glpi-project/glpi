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

/* global glpi_toast_error */

/**
 * Loads the visibility target dropdown fragment when a type is selected.
 *
 * Replaces the legacy inline jQuery scripts that called `ajax/visibility.php`
 * via `$.fn.load()`. The fragment returned by the controller may contain
 * `<script>` tags (Select2 init code emitted by GLPI dropdowns); these are
 * re-executed after injection to preserve the previous behavior.
 */
export class VisibilityTargetController
{
    /** @type {HTMLSelectElement} */
    #trigger;

    /** @type {HTMLElement} */
    #output;

    /** @type {HTMLElement | null} */
    #submit_wrapper;

    /** @type {Record<string, string | number>} */
    #params;

    /**
     * @param {object}                     options
     * @param {HTMLSelectElement | string} options.trigger        - The type <select> element (or its id).
     * @param {HTMLElement | string}       options.output         - The container that receives the fragment (or its id).
     * @param {Record<string, string|number>} options.params      - Base parameters merged with `type` on each request.
     * @param {HTMLElement | string | null} [options.submitWrapper] - Optional wrapper toggled visible after load (or its id).
     */
    constructor({ trigger, output, params, submitWrapper = null })
    {
        this.#trigger = this.#resolveElement(trigger);
        this.#output = this.#resolveElement(output);
        this.#submit_wrapper = submitWrapper ? this.#resolveElement(submitWrapper) : null;
        this.#params = params;

        if (!this.#trigger || !this.#output) {
            return;
        }

        this.#trigger.addEventListener('change', (e) => this.#onChange(e.target.value));
    }

    /**
     * @param {HTMLElement | string} ref
     * @returns {HTMLElement | null}
     */
    #resolveElement(ref)
    {
        return typeof ref === 'string' ? document.getElementById(ref) : ref;
    }

    /**
     * @param {string} type
     */
    async #onChange(type)
    {
        if (type === '') {
            this.#output.replaceChildren();
            this.#toggleSubmit(false);
            return;
        }

        try {
            // Merge params with current type — { ...params, type } ensures the
            // real type overrides the `__VALUE__` placeholder kept in params for
            // legacy callers.
            const body = new URLSearchParams({ ...this.#params, type });

            const response = await fetch(`${CFG_GLPI.root_doc}/Dropdown/VisibilityTarget`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                },
                body,
            });

            if (!response.ok) {
                throw new Error('Failed to load visibility target');
            }

            const html = await response.text();
            this.#injectFragment(html);
            this.#toggleSubmit(true);
        } catch (e) {
            glpi_toast_error(__('An unexpected error occurred.'));
            throw e;
        }
    }

    /**
     * Inject HTML and re-execute any inline `<script>` tags. Setting
     * `innerHTML` does not run scripts on its own; we recreate them so
     * Select2 (and other GLPI dropdown widgets) initialize correctly.
     *
     * @param {string} html
     */
    #injectFragment(html)
    {
        this.#output.innerHTML = html;
        for (const old_script of this.#output.querySelectorAll('script')) {
            const new_script = document.createElement('script');
            for (const attr of old_script.attributes) {
                new_script.setAttribute(attr.name, attr.value);
            }
            new_script.textContent = old_script.textContent;
            old_script.replaceWith(new_script);
        }
    }

    /**
     * @param {boolean} visible
     */
    #toggleSubmit(visible)
    {
        if (!this.#submit_wrapper) {
            return;
        }
        this.#submit_wrapper.classList.toggle('d-none', !visible);
        this.#submit_wrapper.classList.toggle('d-flex', visible);
    }
}
