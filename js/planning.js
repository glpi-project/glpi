var calendar = null;
var GLPIPlanning = {

   display: function(params) {
      // get passed options and merge it with default ones
      var options = (typeof params !== 'undefined')
         ? params: {};
      var default_options = {
         full_view: true,
         default_view: 'timeGridWeek',
         height: GLPIPlanning.getHeight,
         rand: '',
         header: {
            left:   'prev,next,today',
            center: 'title',
            right:  'dayGridMonth, timeGridWeek, timeGridDay, listFull'
         },
      };
      options = Object.assign({}, default_options, options);

      var dom_id         = 'planning'+options.rand;
      var window_focused = true;
      var loaded         = false;
      var disable_qtip   = false;
      var disable_edit   = false;

      /* global FullCalendar */
      calendar = new FullCalendar.Calendar(document.getElementById(dom_id), {
         plugins:     ['dayGrid', 'interaction', 'list', 'timeGrid'],
         height:      options.height,
         theme:       true,
         weekNumbers: options.full_view ? true : false,
         defaultView: options.default_view,
         timeFormat:  'H:mm',
         eventLimit:  true, // show 'more' button when too mmany events
         minTime:     CFG_GLPI.planning_begin,
         maxTime:     CFG_GLPI.planning_end,
         listDayAltFormat: false,
         agendaEventMinHeight: 13,
         header: options.header,
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
            }
         },
         eventRender: function(info) {
            var event = info.event;
            var extProps = event.extendedProps;
            var element = $(info.el);
            var view = info.view;

            var eventtype_marker = '<span class="event_type" style="background-color: '+extProps.typeColor+'"></span>';
            element.find('.fc-content').after(eventtype_marker);
            element.find('.fc-list-item-title > a').prepend(eventtype_marker);

            var content = extProps.content;
            var tooltip = extProps.tooltip;
            if (view.type !== 'dayGridMonth'
               && view.type.indexOf('list') < 0
               && !event.allDay){
               element.append('<div class="content">'+content+'</div>');
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
                  viewport: 'auto'
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
         },
         datesRender: function(info) {
            var view = info.view;

            // force refetch events from ajax on view change (don't refetch on firt load)
            if (loaded) {
               calendar.refetchEvents();
            }

            // specific process for full list
            if (view.type == 'listFull') {
               // hide datepick on full list (which have virtually no limit)
               $('#planning_datepicker')
                  .datepicker('destroy')
                  .hide();

               // hide control buttons
               $('#planning .fc-left .fc-button-group').hide();
            } else {
               // reinit datepicker
               $('#planning_datepicker').show();
               GLPIPlanning.initFCDatePicker(new Date(view.currentStart));

               // show controls buttons
               $('#planning .fc-left .fc-button-group').show();
            }
         },
         eventAfterAllRender: function() {
            // set a var to force refetch events (see viewRender callback)
            loaded = true;

            // scroll div to first element needed to be viewed
            var scrolltoevent = $('#'+dom_id+' .event_past.event_todo').first();
            if (scrolltoevent.length == 0) {
               scrolltoevent = $('#'+dom_id+' .event_today').first();
            }
            if (scrolltoevent.length == 0) {
               scrolltoevent = $('#'+dom_id+' .event_future').first();
            }
            if (scrolltoevent.length == 0) {
               scrolltoevent = $('#'+dom_id+' .event_past').last();
            }
            if (scrolltoevent.length) {
               $('#'+dom_id+' .fc-scroller').scrollTop(scrolltoevent.prop('offsetTop')-25);
            }
         },
         events: {
            url:  CFG_GLPI.root_doc+"/ajax/planning.php",
            type: 'POST',
            extraParams: function() {
               var view_name = calendar
                  ? calendar.state.viewType
                  : options.default_view;
               var display_done_events = 1;
               if (view_name.indexOf('list') >= 0) {
                  display_done_events = 0;
               }
               return {
                  'action': 'get_events',
                  'display_done_events': display_done_events
               };
            },
            success: function(data) {
               if (!options.full_view && data.length == 0) {
                  calendar.setOption('height', 0);
               }
            },
            failure: function() {
               console.error('there was an error while fetching events!');
            }
         },


         // EDIT EVENTS
         editable: true, // we can drag and resize events
         eventResize: function(info) {
            var event = info.event;
            var revertFunc = info.revert;
            GLPIPlanning.editEventTimes(event, revertFunc);
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
         eventDrop: function(info) {
            disable_qtip = false;
            var event = info.event;
            var revertFunc = info.revert;
            GLPIPlanning.editEventTimes(event, revertFunc);
         },
         eventClick: function(info) {
            var event    = info.event;
            var ajaxurl  = event.extendedProps.ajaxurl;
            var editable = event.startEditable && event.durationEditable; // do not know why editable property is not available
            if (ajaxurl && editable && !disable_edit) {
               info.jsEvent.preventDefault(); // don't let the browser navigate
               $('<div>')
                  .dialog({
                     modal:  true,
                     width:  'auto',
                     height: 'auto',
                     close: function() {
                        calendar.refetchEvents();
                     }
                  })
                  .load(ajaxurl, function() {
                     $(this).dialog('option', 'position', ['center', 'center'] );
                  });
            }
         },


         // ADD EVENTS
         selectable: true,
         /*selectHelper: function(start, end) {
            return $('<div class=\"planning-select-helper\" />').text(start+' '+end);
         },*/ // doesn't work anymore: see https://github.com/fullcalendar/fullcalendar/issues/2832
         select: function(info) {
            var start = info.start;
            var end = info.end;
            $('<div>').dialog({
               modal:  true,
               width:  'auto',
               height: 'auto',
               open: function () {
                  $(this).load(
                     CFG_GLPI.root_doc+"/ajax/planning.php",
                     {
                        action: 'add_event_fromselect',
                        begin:  start.toISOString(),
                        end:    end.toISOString()
                     },
                     function() {
                        $(this).dialog('option', 'position', ['center', 'center'] );
                     }
                  );
               },
               position: {
                  my: 'center',
                  at: 'center',
                  viewport: $(window)
               }
            });

            calendar.unselect();
         }
      });

      /* global FullCalendarLocales */
      var loadedLocales = Object.keys(FullCalendarLocales);
      if (loadedLocales.length === 1) {
         calendar.setOption('locale', loadedLocales[0]);
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

      window.calendar = calendar; // Required as object is not accessible by forms callback
      calendar.render();

      // attach button (planning and refresh) in planning header
      $('#'+dom_id+' .fc-toolbar .fc-center h2')
         .after(
            $('<i id="refresh_planning" class="fa fa-sync pointer"></i>')
         ).after(
            $('<input type="hidden" id="planning_datepicker">')
         );

      $('#refresh_planning').click(function() {
         calendar.refetchEvents();
      });

      // attach the date picker to planning
      GLPIPlanning.initFCDatePicker();
   },

   // send ajax for event storage (on event drag/resize)
   editEventTimes: function(event, revertFunc) {
      var extProps = event.extendedProps;

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

      $.ajax({
         url: CFG_GLPI.root_doc+"/ajax/planning.php",
         type: 'POST',
         data: {
            action:   'update_event_times',
            start:    start.toISOString(),
            end:      end.toISOString(),
            itemtype: extProps.itemtype,
            items_id: extProps.items_id
         },
         success: function(html) {
            if (!html) {
               revertFunc();
            }
            calendar.refetchEvents();
            window.displayAjaxMessageAfterRedirect();
         },
         error: function() {
            revertFunc();
         }
      });
   },

   // datepicker for planning
   initFCDatePicker: function(currentDate) {
      $('#planning_datepicker').datepicker({
         changeMonth:     true,
         changeYear:      true,
         numberOfMonths:  3,
         showOn:          'button',
         buttonText:      '<i class="far fa-calendar-alt"></i>',
         dateFormat:      'DD, d MM, yy',
         onSelect: function() {
            var selected_date = $(this).datepicker('getDate');
            calendar.gotoDate(selected_date);
         }
      }).next('.ui-datepicker-trigger').addClass('pointer');

      $('#planning_datepicker').datepicker('setDate', currentDate);
   },

   // set planning height
   getHeight: function() {
      var _newheight = $(window).height() - 272;
      if ($('#debugajax').length > 0) {
         _newheight -= $('#debugajax').height();
      }

      if (CFG_GLPI.glpilayout == 'vsplit') {
         _newheight = $('.ui-tabs-panel').height() - 30;
      }

      //minimal size
      var _minheight = 300;
      if (_newheight < _minheight) {
         _newheight = _minheight;
      }

      return _newheight;
   }
};
