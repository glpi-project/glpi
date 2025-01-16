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

/* global sortable, glpi_toast_info, glpi_toast_error, getAjaxCsrfToken */

export class GlpiHelpdeskConfigController
{
    #container;
    #is_reordering_tiles;
    #profile_id;

    constructor(container, profile_id)
    {
        this.#container = container;
        this.#is_reordering_tiles = false;
        this.#profile_id = profile_id;
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

            // We don't need a class but it won't work if this param is empty.
            placeholderClass: "not-a-real-class",
        });

        sortable(tiles_container)[0].addEventListener('sortstart', () => {
            if (this.#is_reordering_tiles) {
                return;
            }

            this.#is_reordering_tiles = true;
            this.#showReorderUI();
        });
    }

    #initEventsHandlers()
    {
        this.#container
            .querySelector('[data-glpi-helpdesk-config-reorder-action-cancel')
            .addEventListener('click', async () => {
                await this.#reloadTiles();
                this.#hideReorderUI();
                this.#is_reordering_tiles = false;
            })
        ;

        this.#container
            .querySelector('[data-glpi-helpdesk-config-reorder-action-save')
            .addEventListener('click', async() => {
                await this.#saveTilesOrder();
                this.#hideReorderUI();
                this.#is_reordering_tiles = false;
            })
        ;

        this.#container
            .querySelectorAll('[data-glpi-helpdesk-config-action-delete')
            .forEach((node) => {
                node.addEventListener('click', (e) => {
                    const tile = e.target.closest('[data-glpi-helpdesk-config-tile-container]');
                    this.#deleteTile(tile);
                });
            })
        ;
    }

    #showReorderUI()
    {
        this.#container
            .querySelector('[data-glpi-helpdesk-config-reorder-actions]')
            .classList
            .remove('d-none')
        ;
        this.#container
            .querySelectorAll('[data-glpi-helpdesk-config-extra-actions]')
            .forEach((dots) => {
                dots.classList.add('d-none');
            })
        ;
        this.#container
            .querySelectorAll('[data-glpi-helpdesk-config-tile]')
            .forEach((tile_body) => {
                tile_body.classList.add('border-2');
                tile_body.classList.add('border-dashed');
            })
        ;
    }

    #hideReorderUI()
    {
        this.#container
            .querySelector('[data-glpi-helpdesk-config-reorder-actions]')
            .classList
            .add('d-none')
        ;
        this.#container
            .querySelectorAll('[data-glpi-helpdesk-config-extra-actions]')
            .forEach((dots) => {
                dots.classList.remove('d-none');
            })
        ;
        this.#container
            .querySelectorAll('[data-glpi-helpdesk-config-tile]')
            .forEach((tile_body) => {
                tile_body.classList.remove('border-2');
                tile_body.classList.remove('border-dashed');
            })
        ;
    }

    async #reloadTiles()
    {
        try {
            const url = `${CFG_GLPI.root_doc}/Config/Helpdesk/FetchTiles`;
            const url_params = new URLSearchParams({
                profile_id: this.#profile_id,
            });
            const response = await fetch(`${url}?${url_params}`);
            if (!response.ok) {
                throw new Error(response.status);
            }

            this.#getTilesContainerDiv().innerHTML = await response.text();
        } catch (e) {
            glpi_toast_error(__('An unexpected error occurred.'));
            console.error(e);
        }
    }

    async #saveTilesOrder()
    {
        try {
            // Set up form data
            const form_data = new FormData();
            form_data.append('profile_id', this.#profile_id);
            this.#getTilesOrder()
                .forEach((id) => form_data.append("order[]", id))
            ;

            // Send request
            const url = `${CFG_GLPI.root_doc}/ajax/Config/Helpdesk/SetTilesOrder`;
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
            glpi_toast_error(__('An unexpected error occurred.'));
            console.error(e);
        }
    }

    #getTilesOrder()
    {
        const nodes = this.#container
            .querySelectorAll('[data-glpi-helpdesk-config-tile-profile-id]')
        ;

        return [...nodes].map((node) => {
            return node.dataset.glpiHelpdeskConfigTileProfileId;
        });
    }

    #getTilesContainerDiv()
    {
        return this.#container.querySelector("[data-glpi-helpdesk-config-tiles");
    }

    async #deleteTile(tile_container)
    {
        // Hide content immediatly (optimistic UI)
        tile_container.classList.add('d-none');

        try {
            const tile = tile_container.querySelector('[data-glpi-helpdesk-config-tile]');

            // Set up form data
            const form_data = new FormData();
            form_data.append(
                'tile_id',
                tile.dataset.glpiHelpdeskConfigTileId
            );
            form_data.append(
                'tile_itemtype',
                tile.dataset.glpiHelpdeskConfigTileItemtype
            );

            // Send request
            const url = `${CFG_GLPI.root_doc}/ajax/Config/Helpdesk/DeleteTile`;
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
            tile_container.remove();
        } catch (e) {
            glpi_toast_error(__('An unexpected error occurred.'));
            tile_container.classList.remove('d-none');
            console.error(e);
        }
    }
}
