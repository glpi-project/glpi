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

/* global FullCalendar, FullCalendarLocales, FullCalendarInteraction */
/* global glpi_ajax_dialog, glpi_html_dialog */
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
            license_key: "",
            resources: [],
            now: null,
            can_create: false,
            rand: '',
            header: {
                left:   'prev,next,today',
                center: 'title',
                right:  'dayGridMonth, timeGridWeek, timeGridDay, listFull, resourceWeek'
            },
        };
        options = Object.assign({}, default_options, options);

        GLPIPlanning.dom_id = 'planning'+options.rand;
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

        this.calendar = new FullCalendar.Calendar(document.getElementById(GLPIPlanning.dom_id), {
            plugins:     options.plugins,
            height:      options.height,
            timeZone:    'UTC',
            theme:       true,
            weekNumbers: options.full_view ? true : false,
            defaultView: options.default_view,
            timeFormat:  'H:mm',
            eventLimit:  true, // show 'more' button when too mmany events
            minTime:     CFG_GLPI.planning_begin,
            maxTime:     CFG_GLPI.planning_end,
            schedulerLicenseKey: options.license_key,
            resourceAreaWidth: '15%',
            editable: true, // we can drag / resize items
            droppable: false, // we cant drop external items by default
            nowIndicator: true,
            now: options.now,// as we set the calendar as UTC, we need to reprecise the current datetime
            listDayAltFormat: false,
            agendaEventMinHeight: 13,
            header: options.header,
            hiddenDays: hidden_days,
            //resources: options.resources,
            resources: function(fetchInfo, successCallback) {
            // Filter resources by whether their id is in visible_res.
                var filteredResources = [];
                filteredResources = options.resources.filter(function(elem, index) {
                    return GLPIPlanning.visible_res.indexOf(index.toString()) !== -1;
                });

                successCallback(filteredResources);
            },
            views: {
                listFull: {
                    type: 'list',
                    titleFormat: function() {
                        return '';
                    },
                    visibleRange: function(currentDate) {
                        var current_year = currentDate.getFullYear();
                        return {
                            start: (new Date(currentDate.getTime())).setFullYear(current_year - 5),
                            end: (new Date(currentDate.getTime())).setFullYear(current_year + 5)
                        };
                    }
                },
                resourceWeek: {
                    type: 'resourceTimeline',
                    buttonText: 'Timeline Week',
                    duration: { weeks: 1 },
                    //hiddenDays: [6, 0],
                    groupByDateAndResource: true,
                    slotLabelFormat: [
                        { week: 'short' },
                        { weekday: 'short', day: 'numeric', month: 'numeric', omitCommas: true },
                        function(date) {
                            return date.date.hour;
                        }
                    ]
                },
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
                    .prepend('<i class="fas fa-'+icon+'"></i>&nbsp;');

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

                var eventtype_marker = '<span class="event_type" style="background-color: '+extProps.typeColor+'"></span>';
                element.append(eventtype_marker);

                var content = extProps.content;
                var tooltip = extProps.tooltip;
                if (view.type !== 'dayGridMonth'
               && view.type.indexOf('list') < 0
               && event.rendering != "background"
               && !event.allDay){
                    element.append('<div class="content">'+content+'</div>');
                }

                // add icon if exists
                if ("icon" in extProps) {
                    var icon_alt = "";
                    if ("icon_alt" in extProps) {
                        icon_alt = extProps.icon_alt;
                    }

                    element.find(".fc-title, .fc-list-item-title")
                        .append("&nbsp;<i class='"+extProps.icon+"' title='"+icon_alt+"'></i>");
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
                        target: 'mouse',
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

                    // get offset of the event
                    var offset = element.offset();

                    // remove old instances
                    $('.planning-context-menu').remove();

                    // create new one
                    var context = $('<ul class="planning-context-menu" data-event-id=""> \
                  <li class="clone-event"><i class="far fa-clone"></i>'+__("Clone")+'</li> \
                  <li class="delete-event"><i class="fas fa-trash"></i>'+__("Delete")+'</li> \
               </ul>');

                    // add it to body and place it correctly
                    $('body').append(context);
                    context.css({
                        left: offset.left + element.outerWidth() / 4,
                        top: offset.top
                    });

                    // get properties of event for context menu actions
                    var extprops  = event.extendedProps;
                    var resource = {};
                    var actor    = {};

                    if (typeof event.getresources === "function") {
                        resource = event.getresources();
                    }

                    // manage resource changes
                    if (resource.length === 1) {
                        actor = {
                            itemtype: resource[0].extendedProps.itemtype || null,
                            items_id: resource[0].extendedProps.items_id || null,
                        };
                    }

                    // context menu actions
                    // 1- clone event
                    $('.planning-context-menu .clone-event').click(function() {
                        $.ajax({
                            url:  CFG_GLPI.root_doc+"/ajax/planning.php",
                            type: 'POST',
                            data: {
                                action: 'clone_event',
                                event: {
                                    old_itemtype: extprops.itemtype,
                                    old_items_id: extprops.items_id,
                                    actor:        actor,
                                    start:        event.start.toISOString(),
                                    end:          event.end.toISOString(),
                                }
                            },
                            success: function() {
                                GLPIPlanning.refresh();
                            }
                        });
                    });
                    // 2- delete event (manage serie/instance specific events)
                    $('.planning-context-menu .delete-event').click(function() {
                        var ajaxDeleteEvent = function(instance) {
                            instance = instance || false;
                            $.ajax({
                                url:  CFG_GLPI.root_doc+"/ajax/planning.php",
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
                                body: __("Delete the whole serie of the recurrent event") + "<br>" +
                              __("or just add an exception by deleting this instance?"),
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
            datesRender: function(info) {
                var view = info.view;

                // force refetch events from ajax on view change (don't refetch on first load)
                if (loaded) {
                    GLPIPlanning.refresh();
                } else {
                    loaded = true;
                }

                // attach button (planning and refresh) in planning header
                $('#'+GLPIPlanning.dom_id+' .fc-toolbar .fc-center h2')
                    .after(
                        $('<i id="refresh_planning" class="fa fa-sync pointer"></i>')
                    ).after(
                        $('<div id="planning_datepicker"><a data-toggle><i class="far fa-calendar-alt fa-lg pointer"></i></a>')
                    );

                // specific process for full list
                if (view.type == 'listFull') {
                    // hide datepick on full list (which have virtually no limit)
                    if ($('#planning_datepicker').length > 0
                   && "_flatpickr" in $('#planning_datepicker')[0]) {
                        $('#planning_datepicker')[0]._flatpickr.destroy();
                    }
                    $('#planning_datepicker').hide();

                    // hide control buttons
                    $('#planning .fc-left .fc-button-group').hide();
                } else {
                    // reinit datepicker
                    $('#planning_datepicker').show();
                    GLPIPlanning.initFCDatePicker(new Date(view.currentStart));

                    // show controls buttons
                    $('#planning .fc-left .fc-button-group').show();
                }

                // set end of day markers for timeline
                GLPIPlanning.setEndofDays(info.view);

                $('#refresh_planning').on ('click', function() {
                    GLPIPlanning.refresh();
                });
            },
            viewSkeletonRender: function(info) {
                var view_type = info.view.type;

                GLPIPlanning.last_view = view_type;
                // inform backend we changed view (to store it in session)
                $.ajax({
                    url:  CFG_GLPI.root_doc+"/ajax/planning.php",
                    type: 'POST',
                    data: {
                        action: 'view_changed',
                        view:   view_type
                    }
                }).done(function() {
                    // indicate to central page we're done rendering
                    if (!options.full_view) {
                        $(document).trigger('masonry_grid:layout');
                    }
                });

                // set end of day markers for timeline
                GLPIPlanning.setEndofDays(info.view);
            },
            events: {
                url:  CFG_GLPI.root_doc+"/ajax/planning.php",
                type: 'POST',
                extraParams: function() {
                    var view_name = GLPIPlanning.calendar
                        ? GLPIPlanning.calendar.state.viewType
                        : options.default_view;
                    var display_done_events = 1;
                    if (view_name.indexOf('list') >= 0) {
                        display_done_events = 0;
                    }
                    return {
                        'action': 'get_events',
                        'display_done_events': display_done_events,
                        'view_name': view_name
                    };
                },
                success: function(data) {
                    if (!options.full_view && data.length == 0) {
                        GLPIPlanning.calendar.setOption('height', 0);
                    }
                },
                failure: function(error) {
                    console.error('there was an error while fetching events!', error);
                }
            },

            // EDIT EVENTS
            eventResize: function(info) {
                var event        = info.event;
                var exprops      = event.extendedProps;
                var is_recurrent = exprops.is_recurrent || false;

                if (is_recurrent) {
                    glpi_html_dialog({
                        title: __("Recurring event resized"),
                        body: __("The resized event is a recurring event. Do you want to change the serie or instance ?"),
                        buttons: [
                            {
                                label: __("Serie"),
                                click: function() {
                                    GLPIPlanning.editEventTimes(info);
                                }
                            }, {
                                label: _n("Instance", "Instances", 1),
                                click: function() {
                                    GLPIPlanning.editEventTimes(info, true);
                                }
                            }
                        ]
                    });
                } else {
                    GLPIPlanning.editEventTimes(info);
                }
            },
            eventResizeStart: function() {
                disable_edit = true;
                disable_qtip = true;
            },
            eventResizeStop: function() {
                setTimeout(function(){
                    disable_edit = false;
                    disable_qtip = false;
                }, 300);
            },
            eventDragStart: function() {
                disable_qtip = true;
            },
            // event was moved (internal element)
            eventDrop: function(info) {
                disable_qtip = false;

                var event        = info.event;
                var exprops      = event.extendedProps;
                var is_recurrent = exprops.is_recurrent || false;

                if (is_recurrent) {
                    glpi_html_dialog({
                        title: __("Recurring event dragged"),
                        body: __("The dragged event is a recurring event. Do you want to move the serie or instance ?"),
                        buttons: [
                            {
                                label: __("Serie"),
                                click: function() {
                                    GLPIPlanning.editEventTimes(info);
                                }
                            }, {
                                label: _n("Instance", "Instances", 1),
                                click: function() {
                                    GLPIPlanning.editEventTimes(info, true);
                                }
                            }
                        ]
                    });
                } else {
                    GLPIPlanning.editEventTimes(info);
                }
            },
            eventClick: function(info) {
                var event    = info.event;
                var editable = event.extendedProps._editable; // do not know why editable property is not available
                if (event.extendedProps.ajaxurl && editable && !disable_edit) {
                    var start    = event.start;
                    var ajaxurl  = event.extendedProps.ajaxurl+"&start="+start.toISOString();
                    info.jsEvent.preventDefault(); // don't let the browser navigate
                    glpi_ajax_dialog({
                        url: ajaxurl,
                        close: function() {
                            GLPIPlanning.refresh();
                        },
                        dialogclass: 'modal-lg',
                        title: __('Edit an event'),
                    });
                }
            },

            // ADD EVENTS
            selectable: true,
            select: function(info) {
                if (!options.can_create) {
                    GLPIPlanning.calendar.unselect();
                    return false;
                }

                var itemtype = (((((info || {})
                    .resource || {})
                    ._resource || {})
                    .extendedProps || {})
                    .itemtype || '');
                var items_id = (((((info || {})
                    .resource || {})
                    ._resource || {})
                    .extendedProps || {})
                    .items_id || 0);

                // prevent adding events on group users
                if (itemtype === 'Group_User') {
                    GLPIPlanning.calendar.unselect();
                    return false;
                }

                var start = info.start;
                var end = info.end;

                glpi_ajax_dialog({
                    url: CFG_GLPI.root_doc+"/ajax/planning.php",
                    params: {
                        action: 'add_event_fromselect',
                        begin:  start.toISOString(),
                        end:    end.toISOString(),
                        res_itemtype: itemtype,
                        res_items_id: items_id,
                    },
                    dialogclass: 'modal-lg',
                    title: __('Add an event'),
                });

                GLPIPlanning.calendar.unselect();
            }
        });

        var loadedLocales = Object.keys(FullCalendarLocales);
        if (loadedLocales.length === 1) {
            GLPIPlanning.calendar.setOption('locale', loadedLocales[0]);
        }

        $('.planning_on_central a')
            .mousedown(function() {
                disable_qtip = true;
                $('.qtip').hide();
            })
            .mouseup(function() {
                disable_qtip = false;
            });

        window.onblur = function() {
            window_focused = false;
        };
        window.onfocus = function() {
            window_focused = true;
        };

        //window.calendar = calendar; // Required as object is not accessible by forms callback
        GLPIPlanning.calendar.render();

        // attach the date picker to planning
        GLPIPlanning.initFCDatePicker();

        // force focus on the current window
        $(window).focus();

        // remove all context menus on document click
        $(document).click(function() {
            $('.planning-context-menu').remove();
        });
    },

    refresh: function() {
        if (typeof(GLPIPlanning.calendar.refetchResources) == 'function') {
            GLPIPlanning.calendar.refetchResources();
        }
        GLPIPlanning.calendar.refetchEvents();
        GLPIPlanning.calendar.rerenderEvents();
        window.displayAjaxMessageAfterRedirect();
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

    setEndofDays: function(view) {
        // add a class to last col of day in timeline view
        // to visualy separate days
        if (view.constructor.name === "ResourceTimelineView") {
            // compute the number of hour slots displayed
            var time_beg  = CFG_GLPI.planning_begin.split(':');
            var time_end  = CFG_GLPI.planning_end.split(':');
            var int_beg   = parseInt(time_beg[0]) * 60 + parseInt(time_beg[1]);
            var int_end   = parseInt(time_end[0]) * 60 + parseInt(time_end[1]);
            var sec_inter = int_end - int_beg;
            var nb_slots  = Math.ceil(sec_inter / 60);

            // add class to day list header
            $('#planning .fc-time-area.fc-widget-header table tr:nth-child(2) th')
                .addClass('end-of-day');

            // add class to hours list header
            $('#planning .fc-time-area.fc-widget-header table tr:nth-child(3) th:nth-child('+nb_slots+'n)')
                .addClass('end-of-day');

            // add class to content bg (content slots)
            $('#planning .fc-time-area.fc-widget-content table td:nth-child('+nb_slots+'n)')
                .addClass('end-of-day');
        }
    },

    planningFilters: function() {
        $('#planning_filter a.planning_add_filter' ).on( 'click', function( e ) {
            e.preventDefault(); // to prevent change of url on anchor
            var url = $(this).attr('href');
            glpi_ajax_dialog({
                url: url,
                title: __('Add a calendar'),
            });
        });

        $('#planning_filter .filter_option').on( 'click', function() {
            $(this).children('ul').toggle();
        });

        $(document).click(function(e){
            if ($(e.target).closest('#planning_filter .filter_option').length === 0) {
                $('#planning_filter .filter_option ul').hide();
            }
        });

        $('#planning_filter .delete_planning').on( 'click', function() {
            var deleted = $(this);
            var li = deleted.closest('ul.filters > li');
            $.ajax({
                url:  CFG_GLPI.root_doc+"/ajax/planning.php",
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
                url:  CFG_GLPI.root_doc+"/ajax/planning.php",
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
                $.when.apply($, promises).then(function() {
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
                url:  CFG_GLPI.root_doc+"/ajax/planning.php",
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

    // send ajax for event storage (on event drag/resize)
    editEventTimes: function(info, move_instance) {
        move_instance = move_instance || false;

        var event      = info.event;
        var revertFunc = info.revert;
        var extProps   = event.extendedProps;

        var old_itemtype = null;
        var old_items_id = null;
        var new_itemtype = null;
        var new_items_id = null;

        // manage moving the events between resources (users, groups)
        if ("newResource" in info
          && info.newResource !== null) {
            var new_extProps = info.newResource._resource.extendedProps;
            new_itemtype = new_extProps.itemtype;
            new_items_id = new_extProps.items_id;
        }
        if ("oldResource" in info
          && info.oldResource !== null) {
            var old_extProps = info.oldResource._resource.extendedProps;
            old_itemtype = old_extProps.itemtype;
            old_items_id = old_extProps.items_id;
        }

        var start = event.start;
        var end   = event.end;
        if (typeof end === 'undefined' || end === null) {
            end = new Date(start.getTime());
            if (event.allDay) {
                end.setDate(end.getDate() + 1);
            } else {
                end.setHours(end.getHours() + 2);
            }
        }

        var old_event = info.oldEvent || {};
        var old_start = old_event.start || start;

        $.ajax({
            url: CFG_GLPI.root_doc+"/ajax/planning.php",
            type: 'POST',
            data: {
                action:        'update_event_times',
                start:         start.toISOString(),
                end:           end.toISOString(),
                itemtype:      extProps.itemtype,
                items_id:      extProps.items_id,
                move_instance: move_instance,
                old_start:     old_start.toISOString(),
                new_actor_itemtype: new_itemtype,
                new_actor_items_id: new_items_id,
                old_actor_itemtype: old_itemtype,
                old_actor_items_id: old_items_id,
            },
            success: function(html) {
                if (!html) {
                    revertFunc();
                }
                GLPIPlanning.refresh();
            },
            error: function() {
                revertFunc();
            }
        });
    },

    // datepicker for planning
    initFCDatePicker: function(currentDate) {
        $('#planning_datepicker').flatpickr({
            defaultDate: currentDate,
            onChange: function(selected_date) {
            // convert to UTC to avoid timezone issues
                var date = new Date(
                    Date.UTC(
                        selected_date[0].getFullYear(),
                        selected_date[0].getMonth(),
                        selected_date[0].getDate()
                    )
                );
                GLPIPlanning.calendar.gotoDate(date);
            }
        });
    },

    // set planning height
    getHeight: function() {
        var _newheight = $(window).height() - 272;
        if ($('#debugajax').length > 0) {
            _newheight -= $('#debugajax').height();
        }

        //minimal size
        var _minheight = 300;
        if (_newheight < _minheight) {
            _newheight = _minheight;
        }

        return _newheight;
    },
};
