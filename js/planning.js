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

/* eslint prefer-arrow-callback: 0 */
/* eslint no-var: 0 */
/* global FullCalendar, FullCalendarLocales, FullCalendarInteraction */
/* global glpi_ajax_dialog, glpi_html_dialog */
/* global _ */

var GLPIPlanning  = {
    calendar:      null,
    dom_id:        "",
    all_resources: [],
    visible_res:   [],
    drag_object:   null,
    last_view:     null,

    // add/remove resource (like when toggling it in side bar)
    toggleResource: function(res_name, active) {
        // find the index of current resource to find it in our array of visible resources
        var index = GLPIPlanning.all_resources.findIndex(function(current) {
            return current.id == res_name;
        });

        if (index !== -1) {
            // add only if not already present
            if (active && GLPIPlanning.visible_res.indexOf(index.toString()) === -1) {
                GLPIPlanning.visible_res.push(index.toString());
            } else if (!active) {
                GLPIPlanning.visible_res.splice(GLPIPlanning.visible_res.indexOf(index.toString()), 1);
            }
        }
    },

    planningFilters: function() {
        var sendDisplayEvent = function(current_checkbox, refresh_planning) {
            var current_li = current_checkbox.parents('li');
            var parent_name = null;
            if (current_li.parent('ul.group_listofusers').length == 1) {
                parent_name  = current_li
                    .parent('ul.group_listofusers')
                    .parent('li')
                    .attr('event_name');
            }
            var event_name = current_li.attr('event_name');
            var event_type = current_li.attr('event_type');
            var checked    = current_checkbox.is(':checked');

            return $.ajax({
                url:  `${CFG_GLPI.root_doc}/ajax/planning.php`,
                type: 'POST',
                data: {
                    action:  'toggle_filter',
                    name:    event_name,
                    type:    event_type,
                    parent:  parent_name,
                    display: checked
                },
                success: function() {
                    GLPIPlanning.toggleResource(event_name, checked);

                    if (refresh_planning) {
                        // don't refresh planning if event triggered from parent checkbox
                        GLPIPlanning.refresh();
                    }
                }
            });
        };

        $('#planning_filter li:not(li.group_users) input[type="checkbox"]')
            .on( 'click', function() {
                sendDisplayEvent($(this), true);
            });

        $('#planning_filter li.group_users > input[type="checkbox"]')
            .on('change', function() {
                var parent_checkbox    = $(this);
                var parent_li          = parent_checkbox.parents('li');
                var checked            = parent_checkbox.prop('checked');
                var event_name         = parent_li.attr('event_name');
                var chidren_checkboxes = parent_checkbox
                    .parents('li.group_users')
                    .find('ul.group_listofusers input[type="checkbox"]');
                chidren_checkboxes.prop('checked', checked);
                var promises           = [];
                chidren_checkboxes.each(function() {
                    promises.push(sendDisplayEvent($(this), false));
                });

                GLPIPlanning.toggleResource(event_name, checked);

                // refresh planning once for all checkboxes (and not for each)
                // after theirs promises done
                $.when(...promises).then(function() {
                    GLPIPlanning.refresh();
                });
            });

        $('#planning_filter .color_input input').on('change', function() {
            var current_li = $(this).parents('li');
            var parent_name = null;
            if (current_li.length >= 1) {
                parent_name = current_li.eq(1).attr('event_name');
                current_li = current_li.eq(0);
            }
            $.ajax({
                url:  `${CFG_GLPI.root_doc}/ajax/planning.php`,
                type: 'POST',
                data: {
                    action: 'color_filter',
                    name:   current_li.attr('event_name'),
                    type:   current_li.attr('event_type'),
                    parent: parent_name,
                    color: $(this).val()
                },
                success: function() {
                    GLPIPlanning.refresh();
                }
            });
        });

        $('#planning_filter li.group_users .toggle').on('click', function() {
            $(this).closest('.group_users').toggleClass('expanded');
        });

        $('#planning_filter_toggle > a.toggle').on('click', function() {
            $('#planning_filter_content').animate({ width:'toggle' }, 300, 'swing', function() {
                $('#planning_filter').toggleClass('folded');
                $('#planning_container').toggleClass('folded');
            });
        });
    },
};
