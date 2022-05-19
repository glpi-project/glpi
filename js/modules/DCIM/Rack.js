/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

import GLPIModule from "../GLPIModule.js";

/* global escapeMarkupText, GridStack, displayAjaxMessageAfterRedirect */
/* global {GLPI} GLPI */

export default class Rack extends GLPIModule {

    constructor() {
        super();
        this.views = [];
    }

    initialize() {

    }

    /**
     *
     * @param {?jQuery} container
     * @param {number} racks_id
     * @param {number} units
     */
    initRack(container, racks_id, units) {
        if (container === undefined) {
            container = $(document);
        }

        // Handle legacy loading
        if (racks_id === undefined) {
            if (window.grid_rack_id !== undefined && window.grid_rack_units !== undefined) {
                racks_id = window.grid_rack_id;
                units = window.grid_rack_units;
            } else {
                return;
            }
        }
        this.views.push(new RackView(container, racks_id, units));
    }

    initRoom(container, rooms_id, rows, cols) {
        if (container === undefined) {
            container = $(document);
        }
        this.views.push(new DCRoomRackView(container, rooms_id, rows, cols));
    }

    /**
     * Proxy function for {@link RackView.getHpos}.
     * This is mapped to the 'getHpos' global for legacy code.
     * Do not use directly!
     * @param x
     * @param is_half_rack
     * @param is_rack_rear
     * @returns {*|number}
     */
    getHpos(x, is_half_rack, is_rack_rear) {
        const rack_view = this.views.find((view) => view instanceof RackView);
        return rack_view.getHpos(x, is_half_rack, is_rack_rear);
    }

    getLegacyGlobals() {
        return {
            'initRack': 'initRack',
            'getHpos': 'getHpos',
        };
    }
}

class RackView {

    /**
     *
     * @param {jQuery} container
     * @param {number} racks_id
     * @param {number} units
     */
    constructor(container, racks_id, units) {
        this.container = container;
        this.x_before_drag = 0;
        this.y_before_drag = 0;
        this.dirty = false;
        this.link_url = CFG_GLPI.root_doc + '/front/item_rack.form.php';
        this.ajax_url = CFG_GLPI.root_doc + '/ajax/rack.php';
        this.racks_id = racks_id;
        this.units = units;
        this.add_tip = escapeMarkupText(__('Insert an item here'));

        this.registerListeners();

        // init all gridstack found in DOM
        let grids = GridStack.initAll({
            cellHeight: 21,
            margin: 0,
            marginBottom: 1,
            float: true,
            disableOneColumnMode: true,
            animate: true,
            removeTimeout: 100,
            disableResize: true,
        });

        // iterate on each initialized grid to apply events
        grids.forEach((grid) => {
            const is_pdu_grid = $(grid.el).hasClass('side_pdus_graph');

            grid
                .on('dragstart', (e) => {
                    const element = $(e.target);

                    // store position before drag
                    this.x_before_drag = Number(element.attr('gs-x'));
                    this.y_before_drag = Number(element.attr('gs-y'));

                    // disable qtip
                    element.qtip('hide', true);
                })

                // drag&drop scenario for item_rack:
                // - we start by storing position before drag
                // - we send position to db by ajax after drag stop event
                // - if ajax answer return a fail, we restore item to the old position
                //   and we display a message explaning the failure
                // - else we move the other side of asset (if exists)
                .on('change', (event, items) => {
                    if (this.dirty) {
                        return;
                    }
                    const is_rack_rear = $(grid.el).parents('.racks_col').hasClass('rack_rear');
                    $.each(items, (index, item) => {
                        const j_item       = $(item.el);
                        const is_half_rack = j_item.hasClass('half_rack');
                        const new_pos      = this.units
                            - j_item.attr('gs-y')
                            - j_item.attr('gs-h')
                            + 1;

                        $.post(this.ajax_url, {
                            'id': item.id,
                            'action': is_pdu_grid ? 'move_pdu' : 'move_item',
                            'position': new_pos,
                            'hpos': RackView.getHpos(j_item.attr('gs-x'), is_half_rack, is_rack_rear),
                        }, (answer) => {
                            // reset to old position
                            if (!answer.status) {
                                this.dirty = true;
                                grid.update(item.el, {
                                    'x': this.x_before_drag,
                                    'y': this.y_before_drag
                                });
                                this.dirty = false;
                                displayAjaxMessageAfterRedirect();
                            } else {
                                // move other side if needed
                                const other_side_cls = j_item.hasClass('item_rear')
                                    ? "item_front"
                                    : "item_rear";
                                const other_side_el = $('.grid-stack-item.'+other_side_cls+'[gs-id='+j_item.attr('gs-id')+']');

                                if (other_side_el.length) {
                                    //retrieve other side gridstack instance
                                    const other_side_grid = GridStack.init({}, $(other_side_el).closest('.grid-stack')[0]);

                                    // retrieve new coordinates
                                    let new_x = parseInt(j_item.attr('gs-x'));
                                    let new_y = parseInt(j_item.attr('gs-y'));
                                    if (j_item.attr('gs-w') == 1) {
                                        new_x = (j_item.attr('gs-x') == 0 ? 1 : 0);
                                    }
                                    this.dirty = true;

                                    // update other side element coordinates
                                    other_side_grid.update(other_side_el[0], {
                                        'x': new_x,
                                        'y': new_y
                                    });
                                    this.dirty = false;
                                }
                            }
                        }).fail(() => {
                            // reset to old position
                            this.dirty = true;
                            grid.update(item.el, {
                                'x': this.x_before_drag,
                                'y': this.y_before_drag
                            });
                            this.dirty = false;
                            displayAjaxMessageAfterRedirect();
                        });
                    });
                })

                // store coordinates before start dragging
                .on('dragstart', (e) => {
                    const element = $(e.target);

                    this.x_before_drag = Number(element.attr('gs-x'));
                    this.y_before_drag = Number(element.attr('gs-y'));

                    // disable qtip
                    element.qtip('hide', true);
                });
        });

        $('#viewgraph .cell_add, #viewgraph .grid-stack-item').each(function() {
            const tipcontent = $(this).find('.tipcontent');
            if (tipcontent.length) {
                $(this).qtip({
                    position: {
                        my: 'left center',
                        at: 'right center'
                    },
                    content: {
                        text: tipcontent
                    },
                    style: {
                        classes: 'qtip-shadow qtip-bootstrap rack_tipcontent'
                    }
                });
            }
        });

        for (let i = this.units; i >= 1; i--) {
            // add index number front of each rows
            $('.indexes').append(`<li>${i}</li>`);

            // append cells for adding new items
            $('.racks_add').append(
                `<div class="cell_add"><span class="tipcontent">${this.add_tip}</span></div>`
            );
        }
    }

    static get SIDE_FRONT() {
        return 0;
    }

    static get SIDE_REAR() {
        return 1;
    }

    registerListeners() {
        this.container.on('click', '#sviewlist', (e) => {
            $('#viewlist').show();
            $('#viewgraph').hide();
            $(e.currentTarget).addClass('selected');
            $('#sviewgraph').removeClass('selected');
        }).on('click', '#sviewgraph', (e) => {
            $('#viewlist').hide();
            $('#viewgraph').show();
            $(e.currentTarget).addClass('selected');
            $('#sviewlist').removeClass('selected');
        }).on("click", "#toggle_images", (e) => {
            $('#toggle_text').toggle();
            $(e.currentTarget).toggleClass('active');
            $('#viewgraph').toggleClass('clear_picture');
        }).on("click", "#toggle_text", (e) => {
            $(e.currentTarget).toggleClass('active');
            $('#viewgraph').toggleClass('clear_text');
        }).on("click", ".cell_add", (e) =>{
            const index = this.units - $(e.currentTarget).index();
            const side = $(e.currentTarget).parents('.rack_side').hasClass('rack_front') ? RackView.SIDE_FRONT : RackView.SIDE_REAR;

            GLPI.getModule('dialogs').createAjaxDialog({
                url : this.link_url,
                method : 'get',
                dialogclass: 'modal-xl',
                params: {
                    racks_id: this.racks_id,
                    orientation: side,
                    position: index,
                    ajax: true
                }
            });
        }).on("click", "#add_pdu", (e) => {
            e.preventDefault();

            GLPI.getModule('dialogs').createAjaxDialog({
                title: __("Add PDU"),
                url: this.ajax_url,
                method : 'get',
                dialogclass: 'modal-xl',
                params: {
                    racks_id: this.racks_id,
                    action: "show_pdu_form",
                    ajax: true,
                },
            });
        });
    }

    static getHpos(x, is_half_rack, is_rack_rear) {
        if (!is_half_rack) {
            return 0;
        } else if (x == 0 && !is_rack_rear) {
            return 1;
        } else if (x == 0 && is_rack_rear) {
            return 2;
        } else if (x == 1 && is_rack_rear) {
            return 1;
        } else if (x == 1 && !is_rack_rear) {
            return 2;
        }
    }
}

class DCRoomRackView {

    /**
     *
     * @param {jQuery} container
     * @param {number} rooms_id
     * @param {number} rows
     * @param {number} cols
     * @param {[number, number]} cell_size
     */
    constructor(container, rooms_id, rows, cols, cell_size) {
        this.container = container;
        this.rooms_id = rooms_id;
        this.rows = rows;
        this.cols = cols;
        this.rack_form_url = CFG_GLPI.root_doc + '/front/rack.form.php';
        this.ajax_url = CFG_GLPI.root_doc + '/ajax/rack.php';
        this.cell_width = cell_size[0];
        this.cell_height = cell_size[1];

        this.registerListeners();

        GridStack.init({
            column: this.cols,
            maxRow: this.rows + 1,
            cellHeight: this.cell_height,
            margin: 0,
            float: true,
            disableOneColumnMode: true,
            animate: true,
            removeTimeout: 100,
            disableResize: true,
        });
    }

    registerListeners() {
        $(document).on('click', '#sviewlist', function() {
            $('#viewlist').show();
            $('#viewgraph').hide();
            $(this).addClass('selected');
            $('#sviewgraph').removeClass('selected');
        }).on('click', '#sviewgraph', function() {
            $('#viewlist').hide();
            $('#viewgraph').show();
            $(this).addClass('selected');
            $('#sviewlist').removeClass('selected');
        }).on("click", "#toggle_blueprint", function() {
            $(this).toggleClass('active');
            $('#viewgraph').toggleClass('clear_blueprint');
        }).on("click", "#toggle_grid", function() {
            $(this).toggleClass('active');
            $('#viewgraph').toggleClass('clear_grid');
        });
    }

    initStyles() {
        const w_prct = 100 / this.cols;
        let styles = `<style>:root{--dcroom-grid-cellw:${this.cell_width}px;--dcroom-grid-cellh:${this.cell_height}px;}`;
        for (let i = 0; i < this.cols; i++) {
            const left = i * w_prct;
            const width = (i + 1) * w_prct;
            styles += `
                .grid-stack > .grid-stack-item[gs-x='$i'] { left: ${left}%;}
                .grid-stack > .grid-stack-item[gs-w='${i + 1}'] {min-width: ${width}%;width: ${width}%;}
            `;
        }
        styles += '</style>';
        $(document.body).append(styles);
    }
}
