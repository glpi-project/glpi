/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

/* global bootstrap */

/**
 * @since 11.0.10
 */
export default class ItemtypeCommentPopover {
    /**
     * @param {HTMLElement|jQuery} container
     * @param {string[]} info_card_selectors
     */
    constructor(container, info_card_selectors = []) {
        this.container = $(container);
        this.info_card_selectors = info_card_selectors;
        this.requests = {};
        this.#initPopover();
        this.#initInfoCardHoverBehavior();
    }

    #initPopover() {
        this.container.popover({
            selector: '[data-glpi-popover-source]',
            container: this.container,
            html: true,
            sanitize: false,
            trigger: 'hover',
            delay: {
                hide: 300
            },
            content: (el) => {
                $('.popover').popover('hide');
                return $(`#${$(el).attr('data-glpi-popover-source')}`).html();
            }
        }).on('hide.bs.popover', () => {
            for (const selector of this.info_card_selectors) {
                if ($(`${selector}:hover`).length > 0) {
                    // Prevent closing the popover while its info card is hovered
                    return false;
                }
            }
        });
    }

    #initInfoCardHoverBehavior() {
        if (this.info_card_selectors.length === 0) {
            return;
        }

        $(document).on('mouseleave', this.info_card_selectors.join(', '), (e) => {
            const popover = $(e.target).closest('.popover');
            if (popover.length === 0) {
                return;
            }

            setTimeout(() => {
                const popover_element = $(`[data-glpi-popover-source][aria-describedby="${CSS.escape(popover.attr('id'))}"]`);
                if (popover_element.length > 0) {
                    const popover_instance = bootstrap.Popover.getInstance(popover_element[0]);
                    popover_instance.hide();
                }
            }, 300);
        });
    }

    /**
     * @param {HTMLElement|jQuery} element
     * @param {string} itemtype
     * @param {number} items_id
     */
    attachTo(element, itemtype, items_id) {
        const key = `${itemtype}_${items_id}`;

        if (!this.requests[key]) {
            const unique_id = `comment_popover_${itemtype}_${items_id}_${Math.floor(Math.random() * 1000000)}`;

            this.requests[key] = $.ajax({
                url: `${CFG_GLPI.root_doc}/ajax/comments.php`,
                type: 'POST',
                data: {
                    itemtype: itemtype,
                    value: items_id,
                }
            }).then((result) => {
                // `result` is a safe HTML string
                if (result) {
                    this.container.append(`<div id="${unique_id}" style="display: none;">${result}</div>`);
                    return unique_id;
                }
                return null;
            });
        }

        this.requests[key].then((unique_id) => {
            if (unique_id) {
                $(element).attr('data-glpi-popover-source', unique_id);
            }
        });
    }
}
