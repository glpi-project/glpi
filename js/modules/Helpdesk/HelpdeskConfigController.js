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

/* global sortable, glpi_toast_info, glpi_toast_error, getAjaxCsrfToken, bootstrap */

export class GlpiHelpdeskConfigController
{
    #container;
    #itemtype_item;
    #items_id_item;

    constructor(container, itemtype_item, items_id_item)
    {
        this.#container = container;
        this.#itemtype_item = itemtype_item;
        this.#items_id_item = items_id_item;
        this.#enableSortable();
        this.#initEventsHandlers();
    }

    #enableSortable()
    {
        const tiles_container = this.#container
            .querySelector('[data-glpi-helpdesk-config-tiles]')
        ;

        sortable(tiles_container, {
            // Placeholder class.
            placeholder: `<div class="col-12 col-sm-6 col-md-4 d-flex">
                <div class="card my-2 flex-grow-1 border-primary border-dashed border-2 rounded opacity-50">
                </div>
            </div>`,
            handle: '[data-glpi-helpdesk-config-tile-handle]',

            // We don't need a class but it won't work if this param is empty.
            placeholderClass: "not-a-real-class",

            // Specify target items to make sure we exclude the special "add tile" item.
            items: "[data-glpi-draggable-item]",
        });
    }

    #initEventsHandlers()
    {
        this.#container
            .querySelector('[data-glpi-helpdesk-config-reorder-action-save')
            .addEventListener('click', async() => {
                await this.#saveTilesOrder();
            })
        ;

        // Watch for tile deletion
        this.#container.addEventListener('click', (e) => {
            const delete_button = e.target.closest(
                '[data-glpi-helpdesk-config-action-delete]'
            );
            if (delete_button === null) {
                return;
            }

            const data = delete_button.dataset;
            this.#deleteTile(
                e.target.closest('form'),
                data.glpiHelpdeskConfigActionDeleteId,
                data.glpiHelpdeskConfigActionDeleteItemtype,
            );
        });

        // Watch for tile edition
        this.#container.addEventListener('click', (e) => {
            const edit_button = e.target.closest('[data-glpi-helpdesk-config-action-show-edit-form]');
            if (edit_button === null) {
                return;
            }

            const tile = edit_button.closest('[data-glpi-helpdesk-config-tile-container]');
            this.#showEditTileForm(tile);
        });
        this.#container.addEventListener('click', (e) => {
            const save_button = e.target.closest(
                '[data-glpi-helpdesk-config-edit-tile-save]'
            );
            if (save_button === null) {
                return;
            }
            this.#saveTileEdit(e.target.closest('form'));
        });

        // Watch for tile creation
        this.#container.addEventListener('click', (e) => {
            const edit_button = e.target.closest(
                '[data-glpi-helpdesk-config-action-new-tile]'
            );
            if (edit_button === null) {
                return;
            }
            this.#showAddTileForm();
        });
        this.#container.addEventListener('click', (e) => {
            const submit_button = e.target.closest(
                '[data-glpi-helpdesk-config-add-tile-submit]'
            );
            if (submit_button === null) {
                return;
            }
            this.#saveNewTile(e.target.closest('form'));
        });

        // Note: we use jquery here because event listener on select2 doesn't seems to work with vanilla JS.
        $(this.#container).on('change', '[data-glpi-helpdesk-config-add-tile-type]', (e) => {
            this.#triggerTypeChange(e.target.value);
        });
    }

    async #saveTilesOrder()
    {
        try {
            // Set up form data
            const form_data = new FormData();
            form_data.append('itemtype_item', this.#itemtype_item);
            form_data.append('items_id_item', this.#items_id_item);
            this.#getTilesOrder()
                .forEach((id) => form_data.append("order[]", id))
            ;

            // Send request
            const url = `${CFG_GLPI.root_doc}/Config/Helpdesk/SetTilesOrder`;
            const response = await fetch(url, {
                method: 'POST',
                body: form_data,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
                }
            });

            // Handle server errors
            if (!response.ok) {
                throw new Error(response.status);
            }

            // Refresh content and confirm success
            this.#getTilesContainerDiv().innerHTML = await response.text();
            glpi_toast_info(__("Configuration updated successfully."));
        } catch (e) {
            glpi_toast_error(__('An unexpected error occurred'));
            console.error(e);
        }
    }

    #getTilesOrder()
    {
        const nodes = this.#container
            .querySelectorAll('[data-glpi-helpdesk-config-item-tile-id]')
        ;

        return [...nodes].map((node) => {
            return node.dataset.glpiHelpdeskConfigItemTileId;
        });
    }

    #getTilesContainerDiv()
    {
        return this.#container.querySelector("[data-glpi-helpdesk-config-tiles]");
    }

    #getFormTileDiv()
    {
        return this.#container.querySelector('[data-glpi-helpdesk-config-tile-form]');
    }

    #getFormTileLoadingIndicatorDiv()
    {
        return this.#container.querySelector(
            '[data-glpi-helpdesk-config-tile-form-loading]'
        );
    }

    #getFormTileHeader()
    {
        return this.#container.querySelector(
            '[data-glpi-helpdesk-config-tile-form-title]'
        );
    }

    async #deleteTile(form, id, itemtype)
    {
        // Show spinner and disable buttons
        form.querySelector(
            '[data-glpi-helpdesk-config-delete-tile-icon]'
        ).classList.remove('d-none');
        form.querySelector(
            '[data-glpi-helpdesk-config-delete-tile-spinner-icon]'
        ).classList.add('d-none');
        form.querySelector(
            '[data-glpi-helpdesk-config-edit-tile-save]'
        ).disabled = true;
        form.querySelector(
            '[data-glpi-helpdesk-config-action-delete]'
        ).disabled = true;

        try {
            // Set up form data
            const form_data = new FormData();
            form_data.append('tile_id', id);
            form_data.append('tile_itemtype', itemtype);

            // Send request
            const url = `${CFG_GLPI.root_doc}/Config/Helpdesk/DeleteTile`;
            const response = await fetch(url, {
                method: 'POST',
                body: form_data,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
                }
            });

            // Handle server errors
            if (!response.ok) {
                throw new Error(response.status);
            }

            glpi_toast_info(__("Configuration updated successfully."));

            this.#getTilesContainerDiv().innerHTML = await response.text();
            bootstrap.Offcanvas.getInstance('#tile-form-offcanvas').hide();
        } catch (e) {
            glpi_toast_error(__('An unexpected error occurred'));
            console.error(e);
        }
    }

    async #showEditTileForm(tile_container)
    {
        try {
            this.#getFormTileLoadingIndicatorDiv().classList.remove('d-none');
            this.#getFormTileDiv().classList.add('d-none');

            const tile = tile_container.querySelector('[data-glpi-helpdesk-config-tile]');

            const url = `${CFG_GLPI.root_doc}/Config/Helpdesk/ShowEditTileForm`;
            const url_params = new URLSearchParams({
                tile_id: tile.dataset.glpiHelpdeskConfigTileId,
                tile_itemtype: tile.dataset.glpiHelpdeskConfigTileItemtype,
            });
            const response = await fetch(`${url}?${url_params}`);

            // Handle server errors
            if (!response.ok) {
                throw new Error(response.status);
            }

            // Note: we use jQuery instead of raw JS here because we need scripts
            // to be executed for richtext input initialization
            $(this.#getFormTileDiv()).html(await response.text());

            this.#getFormTileHeader().innerHTML = __("Edit tile");
            this.#getFormTileDiv().classList.remove('d-none');
            this.#getFormTileLoadingIndicatorDiv().classList.add('d-none');
        } catch (e) {
            glpi_toast_error(__('An unexpected error occurred'));
            console.error(e);
        }
    }

    async #saveTileEdit(form)
    {
        // Show spinner and disable buttons
        form.querySelector(
            '[data-glpi-helpdesk-config-edit-tile-save-spinner-icon]'
        ).classList.remove('d-none');
        form.querySelector(
            '[data-glpi-helpdesk-config-edit-tile-save-icon]'
        ).classList.add('d-none');
        form.querySelector(
            '[data-glpi-helpdesk-config-edit-tile-save]'
        ).disabled = true;
        form.querySelector(
            '[data-glpi-helpdesk-config-action-delete]'
        ).disabled = true;

        try {
            // Update tinymce values
            if (window.tinymce !== undefined) {
                window.tinymce.get().forEach(editor => {
                    editor.save();
                });
            }

            // Set up form data
            const form_data = new FormData(form);

            // Send request
            const url = `${CFG_GLPI.root_doc}/Config/Helpdesk/UpdateTile`;
            const response = await fetch(url, {
                method: 'POST',
                body: form_data,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
                }
            });

            // Handle server errors
            if (!response.ok) {
                throw new Error(response.status);
            }

            glpi_toast_info(__("Configuration updated successfully."));

            this.#getTilesContainerDiv().innerHTML = await response.text();
            bootstrap.Offcanvas.getInstance('#tile-form-offcanvas').hide();
        } catch (e) {
            glpi_toast_error(__('An unexpected error occurred'));
            console.error(e);
        }
    }

    async #showAddTileForm()
    {
        try {
            this.#getFormTileLoadingIndicatorDiv().classList.remove('d-none');
            this.#getFormTileDiv().classList.add('d-none');

            const url = `${CFG_GLPI.root_doc}/Config/Helpdesk/ShowAddTileForm`;
            const response = await fetch(url);

            // Handle server errors
            if (!response.ok) {
                throw new Error(response.status);
            }

            // Note: we use jQuery instead of raw JS here because we need scripts
            // to be executed for richtext input initialization
            $(this.#getFormTileDiv()).html(await response.text());

            this.#getFormTileHeader().innerHTML = __("Add tile");
            this.#getFormTileDiv().classList.remove('d-none');
            this.#getFormTileLoadingIndicatorDiv().classList.add('d-none');
        } catch (e) {
            glpi_toast_error(__('An unexpected error occurred'));
            console.error(e);
        }
    }

    async #saveNewTile(form)
    {
        // Show spinner and disable button
        form.querySelector(
            '[data-glpi-helpdesk-config-add-tile-submit-spinner-icon]'
        ).classList.remove('d-none');
        form.querySelector(
            '[data-glpi-helpdesk-config-add-tile-submit-plus-icon]'
        ).classList.add('d-none');
        form.querySelector(
            '[data-glpi-helpdesk-config-add-tile-submit]'
        ).disabled = true;

        try {
            // Update tinymce values
            if (window.tinymce !== undefined) {
                window.tinymce.get().forEach(editor => {
                    editor.save();
                });
            }

            // Set up form data
            const form_data = new FormData(form);
            form_data.append('_itemtype_item', this.#itemtype_item);
            form_data.append('_items_id_item', this.#items_id_item);

            // Send request
            const url = `${CFG_GLPI.root_doc}/Config/Helpdesk/AddTile`;
            const response = await fetch(url, {
                method: 'POST',
                body: form_data,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
                }
            });

            // Handle server errors
            if (!response.ok) {
                throw new Error(response.status);
            }

            glpi_toast_info(__("Configuration updated successfully."));

            this.#getTilesContainerDiv().innerHTML = await response.text();
            bootstrap.Offcanvas.getInstance('#tile-form-offcanvas').hide();
        } catch (e) {
            glpi_toast_error(__('An unexpected error occurred'));
            console.error(e);
        }
    }

    #triggerTypeChange(type)
    {
        const submit_button = this.#container.querySelector('[data-glpi-helpdesk-config-add-tile-submit]');

        // Enabled submit button if a type is selected
        if (type == 0) {
            submit_button.classList.add('d-none');
            submit_button.classList.remove('d-flex');
        } else {
            submit_button.classList.remove('d-none');
            submit_button.classList.add('d-flex');
        }

        // Show the correct form
        this.#container
            .querySelectorAll('[data-glpi-helpdesk-config-add-tile-form-for]')
            .forEach((node) => {
                node.classList.add('d-none');
                node.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = true;
                });
            })
        ;
        this.#container
            .querySelectorAll(`[data-glpi-helpdesk-config-add-tile-form-for="${CSS.escape(type)}"]`)
            .forEach((node) => {
                node.classList.remove('d-none');
                node.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = false;
                });
            })
        ;
    }
}
