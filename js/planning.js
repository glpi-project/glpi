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

    display: function(params) {
        // get passed options and merge it with default ones
        var options = (typeof params !== 'undefined')
            ? params: {};
        var default_options = {
            full_view: true,
            default_view: 'timeGridWeek',
            height: GLPIPlanning.getHeight,
            plugins: [
                'dayGrid', 'interaction', 'list', 'timeGrid',
                'resourceTimeline', 'rrule', 'bootstrap'
            ],
            resources: [],
            now: null,
            can_create: false,
            can_delete: false,
            rand: '',
            header: {
                left:   'prev,next,today',
                center: 'title',
                right:  'dayGridMonth, timeGridWeek, timeGridDay, listFull, resourceWeek'
            },
        };
        options = Object.assign({}, default_options, options);

        GLPIPlanning.dom_id = `planning${options.rand}`;
        var window_focused  = true;
        var loaded          = false;
        var disable_qtip    = false;
        var disable_edit    = false;

        // manage visible resources
        this.all_resources = options.resources;
        this.visible_res   = Object.keys(this.all_resources).filter(function(index) {
            return GLPIPlanning.all_resources[index].is_visible;
        });

        // Hide some days depending on GLPI configuration
        var all_days = [0, 1, 2, 3, 4, 5, 6];
        var enabled_days = CFG_GLPI.planning_work_days;
        var hidden_days = all_days.filter(day => !enabled_days.some(n => n == day));
        var loadedLocales = Object.keys(FullCalendarLocales);
        const list_full_year_range = options.full_view ? 5 : 1; // +/- number of years to display in list full view

        this.calendar = new FullCalendar.Calendar(document.getElementById(GLPIPlanning.dom_id), {
            plugins:     options.plugins,
            height:      options.height,
            timeZone:    'UTC',
            theme:       true,
            weekNumbers: options.full_view ? true : false,
            timeFormat:  'H:mm',
            eventLimit:  true, // show 'more' button when too mmany events
            minTime:     CFG_GLPI.planning_begin,
            maxTime:     CFG_GLPI.planning_end,
            schedulerLicenseKey: "GPL-My-Project-Is-Open-Source",
            resourceAreaWidth: '15%',
            editable: true, // we can drag / resize items
            droppable: false, // we cant drop external items by default
            nowIndicator: true,
            now: options.now,// as we set the calendar as UTC, we need to reprecise the current datetime
            listDayAltFormat: false,
            header: options.header,
            hiddenDays: hidden_days,
            locale: loadedLocales.length === 1 ? loadedLocales[0] : undefined,
            //resources: options.resources,
            resources: function(fetchInfo, successCallback) {
            // Filter resources by whether their id is in visible_res.
                var filteredResources = [];
                filteredResources = options.resources.filter(function(elem, index) {
                    return GLPIPlanning.visible_res.indexOf(index.toString()) !== -1;
                });

                successCallback(filteredResources);
            },
            resourceRender: function(info) {
                var icon = "";
                var itemtype = info.resource._resource.extendedProps.itemtype || "";
                switch (itemtype.toLowerCase()) {
                    case "group":
                    case "group_user":
                        icon = "users";
                        break;
                    case "user":
                        icon = "user";
                }
                $(info.el)
                    .find('.fc-cell-text')
                    .prepend(`<i class="ti ti-${icon}"></i>&nbsp;`);

                if (info.resource._resource.extendedProps.itemtype == 'Group_User') {
                    info.el.style.backgroundColor = 'lightgray';
                }
            },
            eventRender: function(info) {
                var event = info.event;
                var extProps = event.extendedProps;
                var element = $(info.el);
                var view = info.view;

                // append event data to dom (to re-use they in clone behavior)
                element.data('myevent', event);

                var eventtype_marker = `<span class="event_type" style="background-color: ${_.escape(extProps.typeColor)}"></span>`;
                element.append(eventtype_marker);

                var content = extProps.content;
                var tooltip = extProps.tooltip;
                if (view.type !== 'dayGridMonth'
               && view.type.indexOf('list') < 0
               && event.rendering != "background"
               && !event.allDay){
                    element.append(`<div class="content">${content}</div>`);
                }

                // add icon if exists
                if ("icon" in extProps) {
                    var icon_alt = "";
                    if ("icon_alt" in extProps) {
                        icon_alt = extProps.icon_alt;
                    }

                    element.find(".fc-title, .fc-list-item-title")
                        .append(`&nbsp;<i class='${_.escape(extProps.icon)}' title='${_.escape(icon_alt)}'></i>`);
                }

                // add classes to current event
                var added_classes = '';
                if (typeof event.end !== 'undefined'
               && event.end !== null) {
                    var now = new Date();
                    var end = event.end;
                    added_classes = end.getTime() < now.getTime()
                        ? ' event_past'   : '';
                    added_classes+= end.getTime() > now.getTime()
                        ? ' event_future' : '';
                    added_classes+= end.toDateString() === now.toDateString()
                        ? ' event_today'  : '';
                }
                if (extProps.state != '') {
                    added_classes+= extProps.state == 0
                        ? ' event_info'
                        : extProps.state == 1
                            ? ' event_todo'
                            : extProps.state == 2
                                ? ' event_done'
                                : '';
                }
                if (added_classes != '') {
                    element.addClass(added_classes);
                }

                // add tooltip to event
                if (!disable_qtip) {
                    // detect ideal position
                    var qtip_position = {
                        target: element,
                        at: 'bottom right',
                        adjust: {
                            mouse: false
                        },
                        viewport: $(window)
                    };
                    if (view.type.indexOf('list') >= 0) {
                        // on central, we want the tooltip on the anchor
                        // because the event is 100% width and so tooltip will be too much on the right.
                        qtip_position.target= element.find('a');
                    }

                    // show tooltips
                    element.qtip({
                        position: qtip_position,
                        content: tooltip,
                        style: {
                            classes: 'qtip-shadow qtip-bootstrap'
                        },
                        show: {
                            solo: true,
                            delay: 100
                        },
                        hide: {
                            fixed: true,
                            delay: 100
                        },
                        events: {
                            show: function(event) {
                                if (!window_focused) {
                                    event.preventDefault();
                                }
                            }
                        }
                    });
                }

                // context menu
                element.on('contextmenu', function(e) {
                    // prevent display of browser context menu
                    e.preventDefault();

                    // get properties of event for context menu actions
                    var extprops  = event.extendedProps;

                    // 2- delete event (manage serie/instance specific events)
                    $('.planning-context-menu .delete-event').on('click', function() {
                        var ajaxDeleteEvent = function(instance) {
                            instance = instance || false;
                            $.ajax({
                                url:  `${CFG_GLPI.root_doc}/ajax/planning.php`,
                                type: 'POST',
                                data: {
                                    action: 'delete_event',
                                    event: {
                                        itemtype: extprops.itemtype,
                                        items_id: extprops.items_id,
                                        day: event.start.toISOString().substring(0, 10),
                                        instance: instance ? 1 : 0,
                                    }
                                },
                                success: function() {
                                    GLPIPlanning.refresh();
                                }
                            });
                        };

                        if (!("is_recurrent" in extprops) || !extprops.is_recurrent) {
                            ajaxDeleteEvent();
                        } else {
                            glpi_html_dialog({
                                title: __("Make a choice"),
                                body: `${__("Delete the whole serie of the recurrent event")}<br>${
                                    __("or just add an exception by deleting this instance?")}`,
                                buttons: [{
                                    label: __("Serie"),
                                    click:  function() {
                                        ajaxDeleteEvent(false);
                                    }
                                }, {
                                    label: _n("Instance", "Instances", 1),
                                    click:  function() {
                                        ajaxDeleteEvent(true);
                                    }
                                }]
                            });
                        }
                    });
                });

            },
        });

        $('.planning_on_central a')
            .mousedown(function() {
                disable_qtip = true;
                $('.qtip').hide();
            })
            .mouseup(function() {
                disable_qtip = false;
            });

        $(window).on('blur', () => {
            window_focused = false;
        });
        $(window).on('focus', () => {
            window_focused = true;
        });

        // force focus on the current window
        $(window).focus();

        $('.fc-scroller').attr('tabindex', '0');
    },

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
        $('#planning_filter .filter_option').on( 'click', function() {
            $(this).children('ul').toggle();
        });

        $(document).click(function(e){
            if ($(e.target).closest('#planning_filter .filter_option').length === 0) {
                $('#planning_filter .filter_option ul').hide();
            }
        });

        $('#planning_filter .delete_planning').on( 'click', function() {
            GLPIPlanning.deletePlanning(this);
        });

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

    deletePlanning: (trigger_element) => {
        const deleted = $(trigger_element);
        const li = deleted.closest('ul.filters > li');
        $.ajax({
            url:  `${CFG_GLPI.root_doc}/ajax/planning.php`,
            type: 'POST',
            data: {
                action: 'delete_filter',
                filter: deleted.attr('value'),
                type: li.attr('event_type')
            },
            success: function() {
                li.remove();
                GLPIPlanning.refresh();
            }
        });
    },

    // set planning height
    getHeight: function() {
        var _newheight = $(window).height() - 272;

        //minimal size
        var _minheight = 300;
        if (_newheight < _minheight) {
            _newheight = _minheight;
        }

        return _newheight;
    },
};
