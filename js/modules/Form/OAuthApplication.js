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

export class OAuthApplicationController
{
    #azure_provider_value;
    #provider_select;
    #tenant_id_field;

    constructor(azure_provider_value) {
        this.#azure_provider_value = azure_provider_value;
        this.#provider_select      = document.querySelector('select[name="provider"]');
        this.#tenant_id_field      = document.querySelector('[data-testid="form-field-tenant_id"]');

        this.#provider_select.addEventListener('change', () => this.#syncTenantIdRequired());
        this.#syncTenantIdRequired();
    }

    #syncTenantIdRequired() {
        const is_azure = this.#provider_select.value === this.#azure_provider_value;
        const input    = this.#tenant_id_field.querySelector('input');
        const label    = this.#tenant_id_field.querySelector('label');

        input.required = is_azure;

        label.querySelector('span.required')?.remove();
        if (is_azure) {
            const span = document.createElement('span');
            span.className = 'required';
            span.textContent = '*';
            label.append(span);
        }
    }
}
