/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/* global grid_link_url, grid_rack_add_tip, grid_rack_id, grid_rack_units, GridStack */
/* global glpi_ajax_dialog, displayAjaxMessageAfterRedirect */
/* global grid_item_ajax_url */

var x_before_drag = 0;
var y_before_drag = 0;
var dirty = false;

var initRack = function() {
    // global grid events
    $(document)
        .on("click", "#sviewlist", function() {
            $('#viewlist').show();
            $('#viewgraph').hide();
            $(this).addClass('selected');
            $('#sviewgraph').removeClass('selected');
        })
        .on("click", "#sviewgraph", function() {
            $('#viewlist').hide();
            $('#viewgraph').show();
            $(this).addClass('selected');
            $('#sviewlist').removeClass('selected');
        })
        .on("click", "#toggle_images", function() {
            $('#toggle_text').toggle();
            $(this).toggleClass('active');
            $('#viewgraph').toggleClass('clear_picture');
        })
        .on("click", "#toggle_text", function() {
            $(this).toggleClass('active');
            $('#viewgraph').toggleClass('clear_text');
        })
        .on("click", ".cell_add", function() {
            var index = grid_rack_units - $(this).index();
            var side = $(this).parents('.rack_side').hasClass('rack_front')
                ? 0  // front
                : 1; // rear

            glpi_ajax_dialog({
                url : grid_link_url,
                method : 'get',
                dialogclass: 'modal-xl',
                params: {
                    racks_id: grid_rack_id,
                    orientation: side,
                    position: index,
                    ajax: true
                }
            });
        })

        .on("click", "#add_pdu", function(event) {
            event.preventDefault();

            glpi_ajax_dialog({
                title: __("Add PDU"),
                url: grid_item_ajax_url,
                method : 'get',
                dialogclass: 'modal-xl',
                params: {
                    racks_id: grid_rack_id,
                    action: "show_pdu_form",
                    ajax: true,
                },
            });
        });


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
    grids.forEach(function(grid) {
        var is_pdu_grid = $(grid.el).hasClass('side_pdus_graph');

        grid
            .on('dragstart', function(event) {
                var element = $(event.target);

                // store position before drag
                x_before_drag = Number(element.attr('gs-x'));
                y_before_drag = Number(element.attr('gs-y'));

                // disable qtip
                element.qtip('hide', true);
            })

        // drag&drop scenario for item_rack:
        // - we start by storing position before drag
        // - we send position to db by ajax after drag stop event
        // - if ajax answer return a fail, we restore item to the old position
        //   and we display a message explaning the failure
        // - else we move the other side of asset (if exists)
            .on('change', function(event, items) {
                if (dirty) {
                    return;
                }
                var is_rack_rear = $(grid.el).parents('.racks_col').hasClass('rack_rear');
                $.each(items, function(index, item) {
                    var j_item       = $(item.el);
                    var is_half_rack = j_item.hasClass('half_rack');
                    var new_pos      = grid_rack_units
                                  - j_item.attr('gs-y')
                                  - j_item.attr('gs-h')
                                  + 1;

                    $.post(grid_item_ajax_url, {
                        'id': item.id,
                        'action': is_pdu_grid ? 'move_pdu' : 'move_item',
                        'position': new_pos,
                        'hpos': getHpos(j_item.attr('gs-x'), is_half_rack, is_rack_rear),
                    }, function(answer) {
                        // reset to old position
                        if (!answer.status) {
                            dirty = true;
                            grid.update(item.el, {
                                'x': x_before_drag,
                                'y': y_before_drag
                            });
                            dirty = false;
                            displayAjaxMessageAfterRedirect();
                        } else {
                            // move other side if needed
                            var other_side_cls = j_item.hasClass('item_rear')
                                ? "item_front"
                                : "item_rear";
                            var other_side_el = $('.grid-stack-item.'+other_side_cls+'[gs-id='+j_item.attr('gs-id')+']');

                            if (other_side_el.length) {
                                //retrieve other side gridstack instance
                                var other_side_grid = GridStack.init({}, $(other_side_el).closest('.grid-stack')[0]);

                                // retrieve new coordinates
                                var new_x = parseInt(j_item.attr('gs-x'));
                                var new_y = parseInt(j_item.attr('gs-y'));
                                if (j_item.attr('gs-w') == 1) {
                                    new_x = (j_item.attr('gs-x') == 0 ? 1 : 0);
                                }
                                dirty = true;

                                // update other side element coordinates
                                other_side_grid.update(other_side_el[0], {
                                    'x': new_x,
                                    'y': new_y
                                });
                                dirty = false;
                            }
                        }
                    }).fail(function() {
                        // reset to old position
                        dirty = true;
                        grid.update(item.el, {
                            'x': x_before_drag,
                            'y': y_before_drag
                        });
                        dirty = false;
                        displayAjaxMessageAfterRedirect();
                    });
                });
            })

        // store coordinates before start dragging
            .on('dragstart', function(event) {
                var element = $(event.target);

                x_before_drag = Number(element.attr('gs-x'));
                y_before_drag = Number(element.attr('gs-y'));

                // disable qtip
                element.qtip('hide', true);
            });
    });

    $('#viewgraph .cell_add, #viewgraph .grid-stack-item').each(function() {
        var tipcontent = $(this).find('.tipcontent');
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

    for (var i = grid_rack_units; i >= 1; i--) {
        // add index number front of each rows
        $('.indexes').append('<li>' + i + '</li>');

        // append cells for adding new items
        $('.racks_add').append(
            '<div class="cell_add"><span class="tipcontent">'+grid_rack_add_tip+'</span></div>'
        );
    }
};


var getHpos = function(x, is_half_rack, is_rack_rear) {
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
};
