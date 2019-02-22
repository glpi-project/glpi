<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Sabre\VObject;

/**
 * Planning Class
**/
class Planning extends CommonGLPI {

   static $rightname = 'planning';

   static $palette_bg = ['#FFEEC4', '#D4EDFB', '#E1D0E1', '#CDD7A9', '#F8C8D2',
                              '#D6CACA', '#D3D6ED', '#C8E5E3', '#FBD5BF', '#E9EBA2',
                              '#E8E5E5', '#DBECDF', '#FCE7F2', '#E9D3D3', '#D2DBDC'];

   static $palette_fg = ['#57544D', '#59707E', '#5B3B5B', '#3A431A', '#58242F',
                              '#3B2727', '#272D59', '#2E4645', '#6F4831', '#46481B',
                              '#4E4E4E', '#274C30', '#6A535F', '#473232', '#454545',];

   static $palette_ev = ['#E94A31', '#5174F2', '#51C9F2', '#FFCC29', '#20C646',
                              '#364959', '#8C5344', '#FF8100', '#F600C4', '#0017FF',
                              '#000000', '#FFFFFF', '#005800', '#925EFF'];

   static $directgroup_itemtype = ['ProjectTask', 'TicketTask', 'ProblemTask', 'ChangeTask'];

   const READMY    =    1;
   const READGROUP = 1024;
   const READALL   = 2048;

   const INFO = 0;
   const TODO = 1;
   const DONE = 2;

   /**
    * @since 0.85
    *
    * @param $nb
   **/
   static function getTypeName($nb = 0) {
      return __('Planning');
   }

   /**
    *  @see CommonGLPI::getMenuContent()
    *
    *   @since 9.1
   **/
   static function getMenuContent() {
      global $CFG_GLPI;

      $menu = [];
      if (static::canView()) {
         $menu['title']   = self::getTypeName();
         $menu['page']    = '/front/planning.php';
      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }


   /**
    * @see CommonGLPI::getMenuShorcut()
    *
    * @since 0.85
   **/
   static function getMenuShorcut() {
      return 'p';
   }


   /**
    * @since 0.85
   **/
   static function canView() {

      return Session::haveRightsOr(self::$rightname, [self::READMY, self::READGROUP,
                                                           self::READALL]);
   }


   function defineTabs($options = []) {

      $ong               = [];
      $ong['no_all_tab'] = true;

      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         $tabs[1] = self::getTypeName();

         return $tabs;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 : // all
               Planning::showPlanning($_SESSION['glpiID']);
               break;
         }
      }
      return true;
   }


   /**
    * Get planning state name
    *
    * @param $value status ID
   **/
   static function getState($value) {

      switch ($value) {
         case static::INFO :
            return _n('Information', 'Information', 1);

         case static::TODO :
            return __('To do');

         case static::DONE :
            return __('Done');
      }
   }


   /**
    * Dropdown of planning state
    *
    * @param $name   select name
    * @param $value  default value (default '')
    * @param $display  display of send string ? (true by default)
    * @param $options  options
   **/
   static function dropdownState($name, $value = '', $display = true, $options = []) {

      $values = [static::INFO => _n('Information', 'Information', 1),
                      static::TODO => __('To do'),
                      static::DONE => __('Done')];

      return Dropdown::showFromArray($name, $values, array_merge(['value'   => $value,
                                                                  'display' => $display], $options));
   }


   /**
    * Check already planned user for a period
    *
    * @param $users_id        user id
    * @param $begin           begin date
    * @param $end             end date
    * @param $except    array of items which not be into account array
    *                         ('Reminder'=>array(1,2,id_of_items))
   **/
   static function checkAlreadyPlanned($users_id, $begin, $end, $except = []) {
      global $CFG_GLPI;

      $planned = false;
      $message = '';

      foreach ($CFG_GLPI['planning_types'] as $itemtype) {
         $data = call_user_func([$itemtype, 'populatePlanning'],
                                ['who'           => $users_id,
                                      'who_group'     => 0,
                                      'whogroup'      => 0,
                                      'begin'         => $begin,
                                      'end'           => $end,
                                      'check_planned' => true]);
         if (isPluginItemType($itemtype)) {
            if (isset($data['items'])) {
               $data = $data['items'];
            } else {
               $data = [];
            }
         }

         if (count($data)
             && method_exists($itemtype, 'getAlreadyPlannedInformation')) {
            foreach ($data as $key => $val) {
               if (!isset($except[$itemtype])
                   || (is_array($except[$itemtype]) && !in_array($val['id'], $except[$itemtype]))) {

                  $planned  = true;
                  $message .= '- '.call_user_func([$itemtype, 'getAlreadyPlannedInformation'],
                                                  $val).'<br>';
               }
            }
         }
      }
      if ($planned) {
         Session::addMessageAfterRedirect(__('The user is busy at the selected timeframe.').
                                          '<br>'.$message, false, WARNING);
      }
      return $planned;
   }


   /**
    * Show the availability of a user
    *
    * @since 0.83
    *
    * @param $params   array of params
    *    must contain :
    *          - begin: begin date to check (default '')
    *          - end: end date to check (default '')
    *          - itemtype : User or Object type (Ticket...)
    *          - foreign key field of the itemtype to define which item to used
    *    optional :
    *          - limitto : limit display to a specific user
    *
    * @return Nothing (display function)
   **/
   static function checkAvailability($params = []) {
      global $CFG_GLPI, $DB;

      if (!isset($params['itemtype'])) {
         return false;
      }
      if (!($item = getItemForItemtype($params['itemtype']))) {
         return false;
      }
      if (!isset($params[$item->getForeignKeyField()])
          || !$item->getFromDB($params[$item->getForeignKeyField()])) {
         return false;
      }
      // No limit by default
      if (!isset($params['limitto'])) {
         $params['limitto'] = 0;
      }
      if (isset($params['begin']) && !empty($params['begin'])) {
         $begin = $params['begin'];
      } else {
         $begin = date("Y-m-d");
      }
      if (isset($params['end']) && !empty($params['end'])) {
         $end = $params['end'];
      } else {
         $end = date("Y-m-d");
      }

      if ($end < $begin) {
         $end = $begin;
      }
      $realbegin = $begin." ".$CFG_GLPI["planning_begin"];
      $realend   = $end." ".$CFG_GLPI["planning_end"];
      if ($CFG_GLPI["planning_end"] == "24:00") {
         $realend = $end." 23:59:59";
      }

      $users = [];

      switch ($item->getType()) {
         case 'User' :
            $users[$item->getID()] = $item->getName();
            break;

         default :
            if (is_a($item, 'CommonITILObject', true)) {
               foreach ($item->getUsers(CommonITILActor::ASSIGN) as $data) {
                  $users[$data['users_id']] = getUserName($data['users_id']);
               }
               foreach ($item->getGroups(CommonITILActor::ASSIGN) as $data) {
                  foreach (Group_User::getGroupUsers($data['groups_id']) as $data2) {
                     $users[$data2['id']] = formatUserName($data2["id"], $data2["name"],
                                                           $data2["realname"], $data2["firstname"]);
                  }
               }
            }
            if ($itemtype = 'Ticket') {
               $task = new TicketTask();
            } else if ($itemtype = 'Problem') {
               $task = new ProblemTask();
            }
            if ($task->getFromDBByCrit(['tickets_id' => $item->fields['id']])) {
               $users['users_id'] = getUserName($task->fields['users_id_tech']);
               $group_id = $task->fields['groups_id_tech'];
               if ($group_id) {
                  foreach (Group_User::getGroupUsers($group_id) as $data2) {
                     $users[$data2['id']] = formatUserName($data2["id"], $data2["name"],
                                                           $data2["realname"], $data2["firstname"]);
                  }
               }
            }
            break;
      }
      asort($users);
      // Use get method to check availability
      echo "<div class='center'><form method='GET' name='form' action='planning.php'>\n";
      echo "<table class='tab_cadre_fixe'>";
      $colspan = 5;
      if (count($users) > 1) {
         $colspan++;
      }
      echo "<tr class='tab_bg_1'><th colspan='$colspan'>".__('Availability')."</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Start')."</td>\n";
      echo "<td>";
      Html::showDateField("begin", ['value'      => $begin,
                                         'maybeempty' => false]);
      echo "</td>\n";
      echo "<td>".__('End')."</td>\n";
      echo "<td>";
      Html::showDateField("end", ['value'      => $end,
                                       'maybeempty' => false]);
      echo "</td>\n";
      if (count($users) > 1) {
         echo "<td width='40%'>";
         $data = [0 => __('All')];
         $data += $users;
         Dropdown::showFromArray('limitto', $data, ['width' => '100%',
                                                         'value' => $params['limitto']]);
         echo "</td>";
      }

      echo "<td class='center'>";
      echo "<input type='hidden' name='".$item->getForeignKeyField()."' value=\"".$item->getID()."\">";
      echo "<input type='hidden' name='itemtype' value=\"".$item->getType()."\">";
      echo "<input type='submit' class='submit' name='checkavailability' value=\"".
             _sx('button', 'Search') ."\">";
      echo "</td>\n";

      echo "</tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>\n";

      if (($params['limitto'] > 0) && isset($users[$params['limitto']])) {
         $displayuser[$params['limitto']] = $users[$params['limitto']];
      } else {
         $displayuser = $users;
      }

      if (count($displayuser)) {
         foreach ($displayuser as $who => $whoname) {
            $params = ['who'       => $who,
                            'who_group' => 0,
                            'whogroup'  => 0,
                            'begin'     => $realbegin,
                            'end'       => $realend];

            $interv = [];
            foreach ($CFG_GLPI['planning_types'] as $itemtype) {
               $interv = array_merge($interv, $itemtype::populatePlanning($params));
            }

            // Print Headers
            echo "<br><div class='center'><table class='tab_cadre_fixe'>";
            $colnumber  = 1;
            $plan_begin = explode(":", $CFG_GLPI["planning_begin"]);
            $plan_end   = explode(":", $CFG_GLPI["planning_end"]);
            $begin_hour = intval($plan_begin[0]);
            $end_hour   = intval($plan_end[0]);
            if ($plan_end[1] != 0) {
               $end_hour++;
            }
            $colsize    = floor((100-15)/($end_hour-$begin_hour));
            $timeheader = '';
            for ($i=$begin_hour; $i<$end_hour; $i++) {
               $from       = ($i<10?'0':'').$i;
               $timeheader.= "<th width='$colsize%' colspan='4'>".$from.":00</th>";
               $colnumber += 4;
            }

            // Print Headers
            echo "<tr class='tab_bg_1'><th colspan='$colnumber'>";
            echo $whoname;
            echo "</th></tr>";
            echo "<tr class='tab_bg_1'><th width='15%'>&nbsp;</th>";
            echo $timeheader;
            echo "</tr>";

            $day_begin = strtotime($realbegin);
            $day_end   = strtotime($realend);

            for ($time=$day_begin; $time<$day_end; $time+=DAY_TIMESTAMP) {
               $current_day   = date('Y-m-d', $time);
               echo "<tr><th>".Html::convDate($current_day)."</th>";
               $begin_quarter = $begin_hour*4;
               $end_quarter   = $end_hour*4;
               for ($i=$begin_quarter; $i<$end_quarter; $i++) {
                  $begin_time = date("Y-m-d H:i:s", strtotime($current_day)+($i)*HOUR_TIMESTAMP/4);
                  $end_time   = date("Y-m-d H:i:s", strtotime($current_day)+($i+1)*HOUR_TIMESTAMP/4);
                  // Init activity interval
                  $begin_act  = $end_time;
                  $end_act    = $begin_time;

                  reset($interv);
                  while ($data = current($interv)) {
                     if (($data["begin"] >= $begin_time)
                         && ($data["end"] <= $end_time)) {
                        // In
                        if ($begin_act > $data["begin"]) {
                           $begin_act = $data["begin"];
                        }
                        if ($end_act < $data["end"]) {
                           $end_act = $data["end"];
                        }
                        unset($interv[key($interv)]);

                     } else if (($data["begin"] < $begin_time)
                                && ($data["end"] > $end_time)) {
                        // Through
                        $begin_act = $begin_time;
                        $end_act   = $end_time;
                        next($interv);

                     } else if (($data["begin"] >= $begin_time)
                                && ($data["begin"] < $end_time)) {
                        // Begin
                        if ($begin_act > $data["begin"]) {
                           $begin_act = $data["begin"];
                        }
                        $end_act = $end_time;
                        next($interv);

                     } else if (($data["end"] > $begin_time)
                                && ($data["end"] <= $end_time)) {
                        //End
                        $begin_act = $begin_time;
                        if ($end_act < $data["end"]) {
                           $end_act = $data["end"];
                        }
                        unset($interv[key($interv)]);

                     } else { // Defautl case
                        next($interv);
                     }
                  }
                  if ($begin_act < $end_act) {
                     if (($begin_act <= $begin_time)
                         && ($end_act >= $end_time)) {
                        // Activity in quarter
                        echo "<td class='notavailable'>&nbsp;</td>";
                     } else {
                        // Not all the quarter
                        if ($begin_act <= $begin_time) {
                           echo "<td class='partialavailableend'>&nbsp;</td>";
                        } else {
                           echo "<td class='partialavailablebegin'>&nbsp;</td>";
                        }
                     }
                  } else {
                     // No activity
                     echo "<td class='available'>&nbsp;</td>";
                  }
               }
               echo "</tr>";
            }
            echo "<tr class='tab_bg_1'><td colspan='$colnumber'>&nbsp;</td></tr>";
            echo "</table></div>";
         }
      }
      echo "<div><table class='tab_cadre'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th>".__('Caption')."</th>";
      echo "<td class='available' colspan=8>".__('Available')."</td>";
      echo "<td class='notavailable' colspan=8>".__('Unavailable')."</td>";
      echo "</tr>";
      echo "</table></div>";

   }


   /**
    * Show the planning
    *
    * Function name change since version 0.84 show() => showPlanning
    * Function prototype changes in 9.1 (no more parameters)
    *
    * @return Nothing (display function)
   **/
   static function showPlanning($fullview = true) {
      global $CFG_GLPI, $DB;

      if (!static::canView()) {
         return false;
      }

      $fullview_str = $fullview?"true":"false";

      $pl_height = "function() {
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
      }";
      if ($_SESSION['glpilayout'] == "vsplit") {
         $pl_height = "function() {
            var _newheight = $('.ui-tabs-panel').height() - 30;
            //minimal size
            var _minheight = 300;
            if (_newheight < _minheight) {
               _newheight = _minheight;
            }
            return _newheight;
         }";
      }

      $date_format = Toolbox::jsDateFormat();

      self::initSessionForCurrentUser();

      echo "<div" . ($fullview ? " id='planning_container'" : "") . ">";
      $rand='';
      if ($fullview) {
         Planning::showPlanningFilter();
         $default_view = "agendaWeek";
         $header = "{
            left:   'prev,next,today',
            center: 'title',
            right:  'month,agendaWeek,agendaDay,listFull'
         }";
      } else {
         $default_view = "listFull";
         $header = "false";
         $pl_height = "'auto'";
         $rand = rand();
      }

      echo "<div id='planning$rand'></div>";
      echo "</div>";
      echo Html::scriptBlock("
      $(function() {
         var disable_qtip = false,
             disable_edit = false;
         $('.planning_on_central a')
            .mousedown(function() {
               disable_qtip = true;
               $('.qtip').hide();
            })
            .mouseup(function() {
               disable_qtip = false;
            });

         var window_focused = true;
         var loaded = false;
         var lastView;
         var lastDate;
         var lastDateDirty = false;
         window.onblur = function() { window_focused = false; }
         window.onfocus = function() { window_focused = true; }

         // datepicker for planning
         var initFCDatePicker = function() {
            $('#planning_datepicker').datepicker({
               changeMonth:     true,
               changeYear:      true,
               numberOfMonths:  3,
               showOn:          'button',
               buttonText:      '<i class=\'far fa-calendar-alt\'></i>',
               dateFormat:      'DD, d MM, yy',
               onSelect: function(dateText, inst) {
                  var selected_date = $(this).datepicker('getDate');
                  $('#planning').fullCalendar('gotoDate', selected_date);
               }
            }).next('.ui-datepicker-trigger').addClass('pointer');
         }

         $('#planning$rand').fullCalendar({
            height:      $pl_height,
            theme:       true,
            weekNumbers: ".($fullview?'true':'false').",
            defaultView: '$default_view',
            timeFormat:  'H:mm',
            eventLimit:  true, // show 'more' button when too mmany events
            minTime:     '".$CFG_GLPI['planning_begin']."',
            maxTime:     '".$CFG_GLPI['planning_end']."',
            listDayAltFormat: false,
            agendaEventMinHeight: 13,
            header: $header,
            views: {
               month: {
                  titleFormat: '$date_format'
               },
               agendaWeek: {
                  titleFormat: '$date_format'
               },
               agendaDay: {
                  titleFormat: '$date_format'
               },
               listFull: {
                  type: 'list',
                  titleFormat: '[]',
                  visibleRange: function(currentDate) {
                     return {
                        start: currentDate.clone().subtract(5, 'years'),
                        end: currentDate.clone().add(5, 'years')
                     };
                 }
               }
            },
            viewRender: function(view){ // on date changes, replicate to datepicker
               var currentdate = view.intervalStart;
               $('#planning_datepicker').datepicker('setDate', new Date(currentdate));
            },
            eventRender: function(event, element, view) {
               var eventtype_marker = '<span class=\"event_type\" style=\"background-color: '+event.typeColor+'\"></span>';
               element.find('.fc-content').after(eventtype_marker);
               element.find('.fc-list-item-title > a').prepend(eventtype_marker);

               var content = event.content;
               var tooltip = event.tooltip;
               if(view.name !== 'month'
                  && view.name.indexOf('list') < 0
                  && !event.allDay){
                  element
                     .append('<div class=\"content\">'+content+'</div>');
               }

               // add classes to current event
               added_classes = '';
               if (typeof event.end !== 'undefined'
                   && event.end !== null) {
                  added_classes = event.end.isBefore(moment())      ? ' event_past'   : '';
                  added_classes+= event.end.isAfter(moment())       ? ' event_future' : '';
                  added_classes+= event.end.isSame(moment(), 'day') ? ' event_today'  : '';
               }
               if (event.state != '') {
                  added_classes+= event.state == 0 ? ' event_info'
                                : event.state == 1 ? ' event_todo'
                                : event.state == 2 ? ' event_done'
                                : '';
               }
               if (added_classes != '') {
                  element.addClass(added_classes);
               }

               // add tooltip to event
               if (!disable_qtip) {
                  var qtip_position = {
                     viewport: 'auto'
                  };
                  if (view.name.indexOf('list') >= 0) {
                     qtip_position.target= element.find('a');
                  }
                  element.qtip({
                     position: qtip_position,
                     content: tooltip,
                     style: {
                        classes: 'qtip-shadow qtip-bootstrap'
                     },
                     show: {
                        solo: true,
                        delay: 400
                     },
                     hide: {
                        fixed: true,
                        delay: 100
                     },
                     events: {
                        show: function(event, api) {
                           if(!window_focused) {
                              event.preventDefault();
                           }
                        }
                     }
                  });
               }
            },
            viewRender: function(view, element) {
               // force refetch events from ajax on view change (don't refetch on firt load)
               if (loaded) {
                  $('#planning$rand').fullCalendar('refetchEvents')
               }

               // specific process for full list
               if (view.name == 'listFull') {
                  // hide datepick on full list (which have virtually no limit)
                  $('#planning_datepicker').datepicker('destroy')
                                           .hide();

                  // hide control buttons
                  $('#planning .fc-left .fc-button-group').hide();
               } else {
                  // reinit datepicker
                  $('#planning_datepicker').show();
                  initFCDatePicker();

                  // show controls buttons
                  $('#planning .fc-left .fc-button-group').show();
               }
            },
            eventAfterAllRender: function(view) {
               // set a var to force refetch events (see viewRender callback)
               loaded = true;

               // scroll div to first element needed to be viewed
               var scrolltoevent = $('#planning$rand .event_past.event_todo').first();
               if (scrolltoevent.length == 0) {
                  scrolltoevent = $('#planning$rand .event_today').first();
               }
               if (scrolltoevent.length == 0) {
                  scrolltoevent = $('#planning$rand .event_future').first();
               }
               if (scrolltoevent.length == 0) {
                  scrolltoevent = $('#planning$rand .event_past').last();
               }
               if (scrolltoevent.length) {
                  $('#planning$rand .fc-scroller').scrollTop(scrolltoevent.prop('offsetTop')-25);
               }
            },
            eventSources: [{
               url:  '".$CFG_GLPI['root_doc']."/ajax/planning.php',
               type: 'POST',
               data: function() {
                  var view_name = $('#planning$rand').fullCalendar('getView').name;
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
                  if (!$fullview_str && data.length == 0) {
                     $('#planning$rand').fullCalendar('option', 'height', 0);
                  }
               },
               error: function() {
                  console.log('there was an error while fetching events!');
               }
            }],

            // EDIT EVENTS
            editable: true, // we can drag and resize events
            eventResize: function(event, delta, revertFunc) {
               editEventTimes(event, revertFunc);
            },
            eventResizeStart: function() {
               disable_edit = true;
            },
            eventResizeStop: function() {
               setTimeout(function(){
                  disable_edit = false;
               }, 300);
            },
            eventDrop: function(event, delta, revertFunc) {
               editEventTimes(event, revertFunc);
            },
            eventClick: function(event) {
               if (event.ajaxurl && event.editable && !disable_edit) {
                  $('<div>')
                     .dialog({
                        modal:  true,
                        width:  'auto',
                        height: 'auto',
                        close: function(event, ui) {
                           $('#planning$rand').fullCalendar('refetchEvents');
                        }
                     })
                     .load(event.ajaxurl, function() {
                        $(this).dialog('option', 'position', ['center', 'center'] );
                     });
                  return false;
               };
            },


            // ADD EVENTS
            selectable: true,
            /*selectHelper: function(start, end) {
               return $('<div class=\"planning-select-helper\" />').text(start+' '+end);
            },*/ // doesn't work anymore: see https://github.com/fullcalendar/fullcalendar/issues/2832
            select: function(start, end, jsEvent) {
               $('<div>').dialog({
                  modal:  true,
                  width:  'auto',
                  height: 'auto',
                  open: function () {
                      $(this).load(
                        '".$CFG_GLPI['root_doc']."/ajax/planning.php?action=add_event_fromselect',
                        {
                           begin: start.format(),
                           end:   end.format()
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

               $('#planning$rand').fullCalendar('unselect');
            }
         });


         // send ajax for event storage (on event drag/resize)
         var editEventTimes = function(event, revertFunc) {
            if (event._allDay) {
               var start = event.start.format()+'T00:00:00';
               var end = start;
               if (typeof event.end != 'undefined') {
                  if (event.end == null) {
                     end = $.fullCalendar.moment(event.start)
                              .add(1, 'days')
                              .format()+'T00:00:00';
                  } else {
                     end = event.end.format()+'T00:00:00';
                  }
               }

            } else {
               var start = event.start.format();
               if (event.end == null) {
                  var end = $.fullCalendar.moment(event.start)
                              .add(2, 'hours')
                              .format();
               } else {
                  var end = event.end.format();
               }
            }

            $.ajax({
               url:  '".$CFG_GLPI['root_doc']."/ajax/planning.php',
               type: 'POST',
               data: {
                  action:   'update_event_times',
                  start:    start,
                  end:      end,
                  itemtype: event.itemtype,
                  items_id: event.items_id
               },
               success: function(html) {
                  if (!html) {
                     revertFunc();
                  }
                  $('#planning$rand').fullCalendar('updateEvent', event);
                  displayAjaxMessageAfterRedirect();
               },
               error: function() {
                  revertFunc();
               }
            });
         };

         // attach button (planning and refresh) in planning header
         $('#planning$rand .fc-toolbar .fc-center h2')
            .after(
               $('<i id=\"refresh_planning\" class=\"fa fa-sync pointer\"></i>')
            ).after(
               $('<input type=\"hidden\" id=\"planning_datepicker\">')
            );

         $('#refresh_planning').click(function() {
            $('#planning$rand').fullCalendar('refetchEvents')
         })

         // attach the date picker to planning
         initFCDatePicker()
      });"
      );
      return;
   }

   /**
    * Return a palette array (for example self::$palette_bg)
    * @param  string $palette_name  the short name for palette (bg, fg, ev)
    * @return mixed                 the palette array or false
    *
    * @since  9.1.1
    */
   static function getPalette($palette_name = 'bg') {
      if (in_array($palette_name, ['bg', 'fg', 'ev'])) {
         return self::${"palette_$palette_name"};
      }

      return false;
   }


   /**
    * Return an hexa color from a palette
    * @param  string  $palette_name the short name for palette (bg, fg, ev)
    * @param  integer $color_index  The color index in this palette
    * @return mixed                 the color in hexa (ex: #FFFFFF) or false
    *
    * @since  9.1.1
    */
   static function getPaletteColor($palette_name = 'bg', $color_index = 0) {
      if ($palette = self::getPalette($palette_name)) {
         if ($color_index > count($palette)) {
            $color_index = $color_index % count($palette);
         }

         return $palette[$color_index];
      }

      return false;
   }


   /**
    * Init $_SESSION['glpi_plannings'] var with thses keys :
    *  - 'filters' : type of planning available (ChangeTask, Reminder, etc)
    *  - 'plannings' : all plannings definided for current user.
    *
    * If currently logged user, has no plannings or filter, this function wiil init them
    *
    * Also manage color index in $_SESSION['glpi_plannings_color_index']
    *
    * @return Nothing (display function)
    */
   static function initSessionForCurrentUser() {
      global $CFG_GLPI;

      // new user in planning, init session
      if (!isset($_SESSION['glpi_plannings']['filters'])) {
         $_SESSION['glpi_plannings']['filters']   = [];
         $_SESSION['glpi_plannings']['plannings'] = ['user_'.$_SESSION['glpiID'] => [
                                                               'color'   => self::getPaletteColor('bg', 0),
                                                               'display' => true,
                                                               'type'    => 'user']];
      }

      // complete missing filters
      $filters = &$_SESSION['glpi_plannings']['filters'];
      $index_color = 0;
      foreach ($CFG_GLPI['planning_types'] as $planning_type) {
         if ($planning_type::canView()) {
            if (!isset($filters[$planning_type])) {
               $filters[$planning_type] = ['color'   => self::getPaletteColor('ev',
                                                                                   $index_color),
                                                'display' => true,
                                                'type'    => 'event_filter'];
            }
            $index_color++;
         }
      }

      // computer color index for plannings
      $_SESSION['glpi_plannings_color_index'] = 0;
      foreach ($_SESSION['glpi_plannings']['plannings'] as $planning) {
         if ($planning['type'] == 'user') {
            $_SESSION['glpi_plannings_color_index']++;

         } else if ($planning['type'] == 'group_users') {
            $_SESSION['glpi_plannings_color_index']+= count($planning['users']);
         }
      }
   }


   /**
    * Display left part of planning who contains filters and planning with delete/toggle buttons
    * and color choosing.
    * Call self::showSingleLinePlanningFilter for each filters and plannings
    *
    * @return Nothing (display function)
    */
   static function showPlanningFilter() {
      global $CFG_GLPI;

      $headings = ['filters'    => __("Events type"),
                        'plannings'  => __('Plannings')];

      echo "<div id='planning_filter'>";

      echo "<div id='planning_filter_toggle'>";
      echo "<a class='toggle pointer' title='".__s("Toggle filters")."'></a>";
      echo "</div>";

      echo "<div id='planning_filter_content'>";
      foreach ($_SESSION['glpi_plannings'] as $filter_heading => $filters) {
         echo "<div>";
         echo "<h3>";
         echo $headings[$filter_heading];
         if ($filter_heading == "plannings") {
             echo "<a class='planning_link planning_add_filter' href='".$CFG_GLPI['root_doc'].
            '/ajax/planning.php?action=add_planning_form'."'>";
            echo "<img class='pointer' src='".$CFG_GLPI['root_doc']."/pics/add_dark.png'>";
            echo "</a>";
         }
         echo "</h3>";
         echo "<ul class='filters'>";
         foreach ($filters as $filter_key => $filter_data) {
            self::showSingleLinePlanningFilter($filter_key,
                                               $filter_data,
                                               ['filter_color_index' => 0]);
         }
         echo "</ul>";
         echo "</div>";
      }
      echo "</div>";
      echo "</div>";

      $ajax_url = $CFG_GLPI['root_doc']."/ajax/planning.php";
      $JS = <<<JAVASCRIPT
      $(function() {
         $('#planning_filter a.planning_add_filter' ).on( 'click', function( e ) {
            e.preventDefault(); // to prevent change of url on anchor
            var url = $(this).attr('href');
            $('<div>').dialog({
               modal: true,
               open: function () {
                   $(this).load(url);
               },
               position: {
                  my: 'top',
                  at: 'center',
                  of: $('#planning_filter')
               }
            });
         });

         $('#planning_filter .filter_option').on( 'click', function( e ) {
            $(this).children('ul').toggle();
         });

         $(document).click(function(e){
            if ($(e.target).closest('#planning_filter .filter_option').length === 0) {
               $('#planning_filter .filter_option ul').hide();
            }
         });

         $('#planning_filter .delete_planning').on( 'click', function( e ) {
            var deleted = $(this);
            var li = deleted.closest('ul.filters > li');
            $.ajax({
               url:  '{$ajax_url}',
               type: 'POST',
               data: {
                  action: 'delete_filter',
                  filter: deleted.attr('value'),
                  type: li.attr('event_type')
               },
               success: function(html) {
                  li.remove();
                  $('#planning').fullCalendar('refetchEvents')
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
            $.ajax({
               url:  '{$ajax_url}',
               type: 'POST',
               data: {
                  action:  'toggle_filter',
                  name:    current_li.attr('event_name'),
                  type:    current_li.attr('event_type'),
                  parent: parent_name,
                  display: current_checkbox.is(':checked')
               },
               success: function(html) {
                  if (refresh_planning) {
                     // don't refresh planning if event triggered from parent checkbox
                     $('#planning').fullCalendar('refetchEvents')
                  }
               }
            });
         }

         $('#planning_filter li:not(li.group_users) input[type="checkbox"]')
            .on( 'click', function( e ) {
               sendDisplayEvent($(this), true);
            }
         );

         $('#planning_filter li.group_users > span > input[type="checkbox"]')
            .on('change', function( e ) {
               var parent_checkbox = $(this);
               var chidren_checkboxes = parent_checkbox
                  .parents('li.group_users')
                  .find('ul.group_listofusers input[type="checkbox"]');
               chidren_checkboxes.prop('checked', parent_checkbox.prop('checked'));
               chidren_checkboxes.each(function(index) {
                  sendDisplayEvent($(this), false);
               });

               // refresh planning once for all checkboxes (and not for each)
               $('#planning').fullCalendar('refetchEvents')
            }
         );

         $('#planning_filter .color_input input').on('change', function(e, color) {
            var current_li = $(this).parents('li');
            var parent_name = null;
            if (current_li.length >= 1) {
               parent_name = current_li.eq(1).attr('event_name');
               current_li = current_li.eq(0)
            }
            $.ajax({
               url:  '{$ajax_url}',
               type: 'POST',
               data: {
                  action: 'color_filter',
                  name:   current_li.attr('event_name'),
                  type:   current_li.attr('event_type'),
                  parent: parent_name,
                  color:  color.toHexString()
               },
               success: function(html) {
                  $('#planning').fullCalendar('refetchEvents')
               }
            });
         });

         $('#planning_filter li.group_users .toggle').on('click', function(e) {
            $(this).parent().toggleClass('expanded');
         });

         $('#planning_filter_toggle > a.toggle').on('click', function(e) {
            $('#planning_filter_content').animate({ width:'toggle' }, 300, 'swing', function() {
               $('#planning_filter').toggleClass('folded');
               $('#planning_container').toggleClass('folded');
            });
         });
      });
JAVASCRIPT;
      echo Html::scriptBlock($JS);
   }


   /**
    * Display a single line of planning filter.
    * See self::showPlanningFilter function
    *
    * @param $filter_key  : identify curent line of filter
    * @param $filter_data : array of filter date, must contains :
    *   * 'show_delete' (boolean): show delete button
    *   * 'filter_color_index' (integer): index of the color to use in self::$palette_bg
    * @param $options
    *
    * @return Nothing (display function)
    */
   static function showSingleLinePlanningFilter($filter_key, $filter_data, $options = []) {
      global $CFG_GLPI;

      $params['show_delete']        = true;
      $params['filter_color_index'] = 0;
      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $actor = explode('_', $filter_key);
      $uID = 0;
      $gID = 0;
      if ($filter_data['type'] == 'user') {
         $uID = $actor[1];
         $user = new User();
         $user->getFromDB($actor[1]);
         $title = $user->getName();
      } else if ($filter_data['type'] == 'group_users') {
         $group = new Group();
         $group->getFromDB($actor[1]);
         $title = $group->getName();
      } else if ($filter_data['type'] == 'group') {
         $gID = $actor[1];
         $group = new Group();
         $group->getFromDB($actor[1]);
         $title = $group->getName();
      } else if ($filter_data['type'] == 'event_filter') {
         if (!($item = getItemForItemtype($filter_key))) {
            return false;
         } else if (!$filter_key::canView()) {
            return false;
         }
         $title = $filter_key::getTypeName();
      }

      echo "<li event_type='".$filter_data['type']."'
               event_name='$filter_key'
               class='".$filter_data['type']."'>";
      Html::showCheckbox(['name'          => 'filters[]',
                               'value'         => $filter_key,
                               'title'         => $title,
                               'checked'       => $filter_data['display']]);

      if ($filter_data['type'] != 'event_filter') {
         $icon_type = explode('_', $filter_data['type']);
         echo "<i class='actor_icon fa fa-fw fa-".$icon_type[0]."'></i>";
      }

      echo "<label for='$filter_key'>$title</label>";

      $color = self::$palette_bg[$params['filter_color_index']];
      if (isset($filter_data['color']) && !empty($filter_data['color'])) {
         $color = $filter_data['color'];
      } else {
         $params['filter_color_index']++;
         $color = self::getPaletteColor('bg', $params['filter_color_index']);
      }

      if ($filter_data['type'] != 'event_filter') {
         echo "<span class='filter_option'>";
         echo "<img class='pointer' src='".$CFG_GLPI['root_doc']."/pics/down.png' />";
         echo "<ul>";
         if ($params['show_delete']) {
            echo "<li class='delete_planning' value='$filter_key'>".__("Delete")."</li>";
         }
         if ($filter_data['type'] != 'group_users') {
            $url = parse_url($CFG_GLPI["url_base"]);
            $port = 80;
            if (isset($url['port'])) {
               $port = $url['port'];
            } else if (isset($url['scheme']) && ($url["scheme"] == 'https')) {
               $port = 443;
            }

            $loginUser = new User();
            $loginUser->getFromDB(Session::getLoginUserID(true));
            $cal_url = "/front/planning.php?genical=1&uID=".$uID."&gID=".$gID.
                       //"&limititemtype=$limititemtype".
                       "&entities_id=".$_SESSION["glpiactive_entity"].
                       "&is_recursive=".$_SESSION["glpiactive_entity_recursive"].
                       "&token=".$loginUser->getAuthToken();

            echo "<li><a target='_blank' href='".$CFG_GLPI["root_doc"]."$cal_url'>".
                 _sx("button", "Export")." - ".__("Ical")."</a></li>";

            echo "<li><a target='_blank' href='webcal://".$url['host'].":$port".
                 (isset($url['path'])?$url['path']:'')."$cal_url'>".
                 _sx("button", "Export")." - ".__("Webcal")."</a></li>";
         }
         echo "</ul>";
         echo "</span>";
      }

      // colors not for groups
      if ($filter_data['type'] != 'group_users') {
         echo "<span class='color_input'>";
         Html::showColorField($filter_key."_color",
                              ['value' => $color]);
         echo "</span>";
      }
      if ($filter_data['type'] == 'group_users') {
         echo "<span class='toggle pointer' />";
      }

      if ($filter_data['type'] == 'group_users') {
         echo "<ul class='group_listofusers filters'>";
         foreach ($filter_data['users'] as $user_key => $userdata) {
            self::showSingleLinePlanningFilter($user_key,
                                               $userdata,
                                               ['show_delete'        => false,
                                                     'filter_color_index' => $params['filter_color_index']]);
         }
         echo "</ul>";
      }

      echo "</li>";
   }


   /**
    * Display ajax form to add actor on planning
    *
    * @return Nothing (display function)
    */
   static function showAddPlanningForm() {
      global $CFG_GLPI;

      $rand = mt_rand();
      echo "<form action='".self::getFormURL()."'>";
      echo __("Actor").": <br>";

      $planning_types = ['user' => __("User")];

      if (Session::haveRightsOr('planning', [self::READGROUP, self::READALL])) {
         $planning_types['group_users'] = __('All users of a group');
         $planning_types['group']       = __('Group');
      }

      Dropdown::showFromArray('planning_type',
                              $planning_types,
                              ['display_emptychoice' => true,
                                    'rand'                =>  $rand]);
      echo Html::scriptBlock("
      $(function() {
         $('#dropdown_planning_type$rand').on( 'change', function( e ) {
            var planning_type = $(this).val();
            $('#add_planning_subform$rand').load('".$CFG_GLPI['root_doc']."/ajax/planning.php',
                                                 {action: 'add_'+planning_type+'_form'});
         });
      });");
      echo "<br><br>";
      echo "<div id='add_planning_subform$rand'></div>";
      Html::closeForm();
   }


   /**
    * Display 'User' part of self::showAddPlanningForm spcified by planning type dropdown.
    * Actually called by ajax/planning.php
    *
    * @return Nothing (display function)
    */
   static function showAddUserForm() {
      global $CFG_GLPI;

      $rand = mt_rand();
      $used = [];
      foreach (array_keys($_SESSION['glpi_plannings']) as $actor) {
         $actor = explode("_", $actor);
         if ($actor[0] == "user") {
            $used[] = $actor[1];
         }
      }
      echo __("User")." :<br>";

      // show only users with right to add planning events
      $rights = ['change', 'problem', 'reminder', 'task', 'projecttask'];
      // Can we see only personnal planning ?
      if (!Session::haveRightsOr('planning', [self::READALL, self::READGROUP])) {
         $rights = 'id';
      }
      // Can we see user of my groups ?
      if (Session::haveRight('planning', self::READGROUP)
          && !Session::haveRight('planning', self::READALL)) {
         $rights = 'groups';
      }

      User::dropdown(['entity'      => $_SESSION['glpiactive_entity'],
                           'entity_sons' => $_SESSION['glpiactive_entity_recursive'],
                           'right'       => $rights,
                           'used'        => $used]);
      echo "<br /><br />";
      echo Html::hidden('action', ['value' => 'send_add_user_form']);
      echo Html::submit(_sx('button', 'Add'));
   }


   /**
    * Recieve 'User' data from self::showAddPlanningForm and save them to session and DB
    *
    * @param $params (array) : must contais form data (typically $_REQUEST)
    */
   static function sendAddUserForm($params = []) {
      $_SESSION['glpi_plannings']['plannings']["user_".$params['users_id']]
         = ['color'   => self::getPaletteColor('bg', $_SESSION['glpi_plannings_color_index']),
                 'display' => true,
                 'type'    => 'user'];
      self::savePlanningsInDB();
      $_SESSION['glpi_plannings_color_index']++;
   }


   /**
    * Display 'All users of a group' part of self::showAddPlanningForm spcified by planning type dropdown.
    * Actually called by ajax/planning.php
    *
    * @return Nothing (display function)
    */
   static function showAddGroupUsersForm() {
      echo __("Group")." : <br>";

      $condition = ['is_task' => 1];
      // filter groups
      if (!Session::haveRight('planning', self::READALL)) {
         $condition['id'] = $_SESSION['glpigroups'];
      }

      Group::dropdown([
         'entity'      => $_SESSION['glpiactive_entity'],
         'entity_sons' => $_SESSION['glpiactive_entity_recursive'],
         'condition'   => $condition
      ]);
      echo "<br /><br />";
      echo Html::hidden('action', ['value' => 'send_add_group_users_form']);
      echo Html::submit(_sx('button', 'Add'));
   }


   /**
    * Recieve 'All users of a group' data from self::showAddGroupUsersForm and save them to session and DB
    *
    * @since 9.1
    *
    * @param $params (array) : must contais form data (typically $_REQUEST)
    */
   static function sendAddGroupUsersForm($params = []) {
      $current_group = &$_SESSION['glpi_plannings']['plannings']["group_".$params['groups_id']."_users"];
      $current_group = ['display' => true,
                        'type'    => 'group_users',
                        'users'   => []];
      $users = Group_User::getGroupUsers($params['groups_id'], [
         'glpi_users.is_active'  => 1,
         'glpi_users.is_deleted' => 0,
         [
            'OR' => [
               ['glpi_users.begin_date' => null],
               ['glpi_users.begin_date' => ['<', new QueryExpression('NOW()')]],
            ],
         ],
         [
            'OR' => [
               ['glpi_users.end_date' => null],
               ['glpi_users.end_date' => ['>', new QueryExpression('NOW()')]],
            ]
         ]
      ]);
      $index_color = count($_SESSION['glpi_plannings']['plannings']);
      $group_user_index = 0;
      foreach ($users as $user_data) {
         // do not add an already set user
         if (!isset($_SESSION['glpi_plannings']['plannings']['user_'.$user_data['id']])) {
            $current_group['users']['user_'.$user_data['id']] = [
               'color'   => self::getPaletteColor('bg', $_SESSION['glpi_plannings_color_index']),
               'display' => true,
               'type'    => 'user'
            ];
            $_SESSION['glpi_plannings_color_index']++;
         }
      }
      self::savePlanningsInDB();
   }


   static function editEventForm($params = []) {

      if (!$params['itemtype'] instanceof CommonDBTM) {
         echo "<div class='center'>";
         echo "<a href='".$params['url']."'>".__("View this item in his context")."</a>";
         echo "</div>";
         echo "<hr>";
         $rand = mt_rand();
         $options = [
            'from_planning_edit_ajax' => true,
            'formoptions'             => "id='edit_event_form$rand'"
         ];
         if (isset($params['parentitemtype'])) {
            $options['parent'] = getItemForItemtype($params['parentitemtype']);
            $options['parent']->getFromDB($params['parentid']);
         }
         $item = getItemForItemtype($params['itemtype']);
         $item->showForm(intval($params['id']), $options);
         $callback = "$('.ui-dialog-content').dialog('close');
                      $('#planning').fullCalendar('refetchEvents');
                      displayAjaxMessageAfterRedirect();";
         Html::ajaxForm("#edit_event_form$rand", $callback);
      }
   }


   /**
    * Display 'Group' part of self::showAddPlanningForm spcified by planning type dropdown.
    * Actually called by ajax/planning.php
    *
    * @since 9.1
    *
    * @return Nothing (display function)
    */
   static function showAddGroupForm($params = []) {

      $condition = ['is_task' => 1];
      // filter groups
      if (!Session::haveRight('planning', self::READALL)) {
         $condition['id'] = $_SESSION['glpigroups'];
      }

      echo __("Group")." : <br>";
      Group::dropdown([
         'entity'      => $_SESSION['glpiactive_entity'],
         'entity_sons' => $_SESSION['glpiactive_entity_recursive'],
         'condition'   => $condition
      ]);
      echo "<br /><br />";
      echo Html::hidden('action', ['value' => 'send_add_group_form']);
      echo Html::submit(_sx('button', 'Add'));
   }


   /**
    * Recieve 'Group' data from self::showAddGroupForm and save them to session and DB
    *
    * @since 9.1
    *
    * @param $params (array) : must contais form data (typically $_REQUEST)
    */
   static function sendAddGroupForm($params = []) {
      $_SESSION['glpi_plannings']['plannings']["group_".$params['groups_id']]
         = ['color'   => self::getPaletteColor('bg',
                                                    $_SESSION['glpi_plannings_color_index']),
                 'display' => true,
                 'type'    => 'group'];
      self::savePlanningsInDB();
      $_SESSION['glpi_plannings_color_index']++;
   }


   static function showAddEventForm($params = []) {
      global $CFG_GLPI;

      if (count ($CFG_GLPI['planning_add_types']) == 1) {
         $params['itemtype'] = $CFG_GLPI['planning_add_types'][0];
         self::showAddEventSubForm($params);
      } else {
         $rand = mt_rand();
         $select_options = [];
         foreach ($CFG_GLPI['planning_add_types'] as $add_types) {
            $select_options[$add_types] = $add_types::getTypeName(1);
         }
         echo __("Event type")." : <br>";
         Dropdown::showFromArray('itemtype',
                                 $select_options,
                                 ['display_emptychoice' => true,
                                       'rand'                => $rand]);

         echo Html::scriptBlock("
         $(function() {
            $('#dropdown_itemtype$rand').on('change', function() {
               var current_itemtype = $(this).val();
               $('#add_planning_subform$rand').load('".$CFG_GLPI['root_doc']."/ajax/planning.php',
                                                    {action:   'add_event_sub_form',
                                                     itemtype: current_itemtype,
                                                     begin:    '".$params['begin']."',
                                                     end:      '".$params['end']."'});
            });
         });");
         echo "<br><br>";
         echo "<div id='add_planning_subform$rand'></div>";
      }
   }


   /**
    * Display form after selecting date range in planning
    *
    * @since 9.1
    *
    * @param $params (array): must contains this keys :
    *  - begin : start of selection range.
    *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
    *  - end : end of selection range.
    *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
    *
    * @return Nothing (display function)
    */
   static function showAddEventSubForm($params = []) {

      $rand            = mt_rand();
      $params['begin'] = date("Y-m-d H:i:s", strtotime($params['begin']));
      $params['end']   = date("Y-m-d H:i:s", strtotime($params['end']));
      if ($item = getItemForItemtype($params['itemtype'])) {
         $item->showForm('', ['from_planning_ajax' => true,
                                   'begin'              => $params['begin'],
                                   'end'                => $params['end'],
                                   'formoptions'        => "id='ajax_reminder$rand'"]);
         $callback = "$('.ui-dialog-content').dialog('close');
                      $('#planning').fullCalendar('refetchEvents');
                      displayAjaxMessageAfterRedirect();";
         Html::ajaxForm("#ajax_reminder$rand", $callback);
      }
   }


   /**
    * Former front/planning.php before 9.1.
    * Display a classic form to plan an event (with begin fiel and duration)
    *
    * @since 9.1
    *
    * @param $params (array): array of parameters whou should contain :
    *   - id (integer): id of item who receive the planification
    *   - itemtype (string): itemtype of item who receive the planification
    *   - begin (string) : start date of event
    *   - end (optionnal) (string) : end date of event. Ifg missing, it will computerd from begin+1hour
    *   - rand_user (integer) : users_id to check planning avaibility
    */
   static function showAddEventClassicForm($params = []) {
      global $CFG_GLPI;

      if (isset($params["id"]) && ($params["id"] > 0)) {
         echo "<input type='hidden' name='plan[id]' value='".$params["id"]."'>";
      }

      $mintime = $CFG_GLPI["planning_begin"];
      if (isset($params["begin"]) && !empty($params["begin"])) {
         $begin = $params["begin"];
         $begintime = date( "H:i:s", strtotime($begin));
         if ($begintime < $mintime) {
            $mintime = $begintime;
         }

      } else {
         $ts = $CFG_GLPI['time_step'] * 60; // passage en minutes
         $time = time() + $ts - 60;
         $time = floor($time / $ts) * $ts;
         $begin = date("Y-m-d H:i", $time);
      }

      if (isset($params["end"]) && !empty($params["end"])) {
         $end = $params["end"];

      } else {
         $end = date("Y-m-d H:i:s", strtotime($begin)+HOUR_TIMESTAMP);
      }

      echo "<table class='tab_cadre'>";

      echo "<tr class='tab_bg_2'><td>".__('Start date')."</td><td>";
      $rand_begin = Html::showDateTimeField("plan[begin]",
                                            ['value'      => $begin,
                                                  'timestep'   => -1,
                                                  'maybeempty' => false,
                                                  'canedit'    => true,
                                                  'mindate'    => '',
                                                  'maxdate'    => '',
                                                  'mintime'    => $mintime,
                                                  'maxtime'    => $CFG_GLPI["planning_end"]]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td>".__('Period')."&nbsp;";

      if (isset($params["rand_user"])) {
         echo "<span id='user_available".$params["rand_user"]."'>";
         include_once(GLPI_ROOT.'/ajax/planningcheck.php');
         echo "</span>";
      }

      echo "</td><td>";

      $default_delay = floor((strtotime($end)-strtotime($begin))/$CFG_GLPI['time_step']/MINUTE_TIMESTAMP)*$CFG_GLPI['time_step']*MINUTE_TIMESTAMP;

      $rand = Dropdown::showTimeStamp("plan[_duration]", ['min'        => 0,
                                                               'max'        => 50*HOUR_TIMESTAMP,
                                                               'value'      => $default_delay,
                                                               'emptylabel' => __('Specify an end date')]);
      echo "<br><div id='date_end$rand'></div>";

      $event_options = ['duration'     => '__VALUE__',
                             'end'          => $end,
                             'name'         => "plan[end]",
                             'global_begin' => $CFG_GLPI["planning_begin"],
                             'global_end'   => $CFG_GLPI["planning_end"]];

      Ajax::updateItemOnSelectEvent("dropdown_plan[_duration]$rand", "date_end$rand",
                                    $CFG_GLPI["root_doc"]."/ajax/planningend.php", $event_options);

      if ($default_delay == 0) {
         $params['duration'] = 0;
         Ajax::updateItem("date_end$rand", $CFG_GLPI["root_doc"]."/ajax/planningend.php", $params);
      }

      echo "</td></tr>\n";

      if ((!isset($params["id"]) || ($params["id"] == 0))
          && isset($params['itemtype'])
          && PlanningRecall::isAvailable()) {
         echo "<tr class='tab_bg_2'><td>"._x('Planning', 'Reminder')."</td><td>";
         PlanningRecall::dropdown(['itemtype' => $params['itemtype'],
                                        'items_id' => $params['items_id']]);
         echo "</td></tr>";
      }
      echo "</table>\n";
   }


   /**
    * toggle display for selected line of $_SESSION['glpi_plannings']
    *
    * @since 9.1
    *
    * @param  array $options: should contains :
    *  - type : event type, can be event_filter, user, group or group_users
    *  - parent : in case of type=users_group, must contains the id of the group
    *  - name : contains a string with type and id concatened with a '_' char (ex user_41).
    *  - display : boolean value to set to his line
    * @return nothing
    */
   static function toggleFilter($options = []) {

      $key = 'filters';
      if (in_array($options['type'], ['user', 'group'])) {
         $key = 'plannings';
      }
      if (!isset($options['parent'])
          || empty($options['parent'])) {
         $_SESSION['glpi_plannings'][$key][$options['name']]['display']
            = ($options['display'] === 'true');
      } else {
         $_SESSION['glpi_plannings']['plannings'][$options['parent']]['users']
            [$options['name']]['display']
            = ($options['display'] === 'true');
      }
      self::savePlanningsInDB();
   }


   /**
    * change color for selected line of $_SESSION['glpi_plannings']
    *
    * @since 9.1
    *
    * @param  array $options: should contains :
    *  - type : event type, can be event_filter, user, group or group_users
    *  - parent : in case of type=users_group, must contains the id of the group
    *  - name : contains a string with type and id concatened with a '_' char (ex user_41).
    *  - color : rgb color (preceded by '#'' char)
    * @return nothing
    */
   static function colorFilter($options = []) {
      $key = 'filters';
      if (in_array($options['type'], ['user', 'group'])) {
         $key = 'plannings';
      }
      if (!isset($options['parent'])
          || empty($options['parent'])) {
         $_SESSION['glpi_plannings'][$key][$options['name']]['color'] = $options['color'];
      } else {
         $_SESSION['glpi_plannings']['plannings'][$options['parent']]['users']
            [$options['name']]['color'] = $options['color'];
      }
      self::savePlanningsInDB();
   }


   /**
    * delete selected line in $_SESSION['glpi_plannings']
    *
    * @since 9.1
    *
    * @param  array $options: should contains :
    *  - type : event type, can be event_filter, user, group or group_users
    *  - filter : contains a string with type and id concatened with a '_' char (ex user_41).
    * @return nothing
    */
   static function deleteFilter($options = []) {

      $current = &$_SESSION['glpi_plannings']['plannings'][$options['filter']];
      if (in_array($options['type'], ['user', 'group'])) {
         $_SESSION['glpi_plannings_color_index']--;

      } else if ($current['type'] = 'group_users') {
         $_SESSION['glpi_plannings_color_index']-= count($current['users']);
      }

      unset($_SESSION['glpi_plannings']['plannings'][$options['filter']]);
      self::savePlanningsInDB();
   }


   static function savePlanningsInDB() {

      $user = new User;
      $user->update(['id' => $_SESSION['glpiID'],
                          'plannings' => exportArrayToDB($_SESSION['glpi_plannings'])]);
   }


   /**
    * Prepare a set of events for jquery fullcalendar.
    * Call populatePlanning functions for all $CFG_GLPI['planning_types'] types
    *
    * @since 9.1
    *
    * @param array $options with this keys:
    *  - begin: mandatory, planning start.
    *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
    *  - end: mandatory, planning end.
    *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
    *  - display_done_events: default true, show also events tagged as done
    * @return array $events : array with events in fullcalendar.io format
    */
   static function constructEventsArray($options = []) {
      global $CFG_GLPI;

      $param['start']               = '';
      $param['end']                 = '';
      $param['display_done_events'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }
      $param['begin'] = date("Y-m-d H:i:s", strtotime($param['start']));
      $param['end']   = date("Y-m-d H:i:s", strtotime($param['end']));

      $raw_events = [];
      foreach ($CFG_GLPI['planning_types'] as $planning_type) {
         if (!$planning_type::canView()) {
            continue;
         }
         if ($_SESSION['glpi_plannings']['filters'][$planning_type]['display']) {
            $event_type_color = $_SESSION['glpi_plannings']['filters'][$planning_type]['color'];
            foreach ($_SESSION['glpi_plannings']['plannings'] as $actor => $actor_params) {
               $actor_params['event_type_color'] = $event_type_color;
               $actor_params['planning_type'] = $planning_type;
               self::constructEventsArraySingleLine($actor,
                                                    array_merge($param, $actor_params),
                                                    $raw_events);
            }
         }
      }

      // construct events (in fullcalendar format)
      $events = [];
      foreach ($raw_events as $event) {
         $users_id = (isset($event['users_id_tech']) && !empty($event['users_id_tech'])?
                        $event['users_id_tech']:
                        $event['users_id']);
         $content = Planning::displayPlanningItem($event, $users_id, 'in', false);
         $tooltip = Planning::displayPlanningItem($event, $users_id, 'in', true);

         $begin = date('c', strtotime($event['begin']));
         $end = date('c', strtotime($event['end']));

         // retreive all day events
         if (strpos($event['begin'], "00:00:00") != false
              && (strtotime($event['end']) - strtotime($event['begin'])) % DAY_TIMESTAMP == 0) {
            $begin = date('Y-m-d', strtotime($event['begin']));
            $end = date('Y-m-d', strtotime($event['end']));
         }

         $index_color = array_search("user_$users_id", array_keys($_SESSION['glpi_plannings']));
         $events[] = ['title'       => $event['name'],
                           'content'     => $content,
                           'tooltip'     => $tooltip,
                           'start'       => $begin,
                           'end'         => $end,
                           'editable'    => isset($event['editable'])?$event['editable']:false,
                           'color'       => (empty($event['color'])?
                                             Planning::$palette_bg[$index_color]:
                                             $event['color']),
                           'borderColor' => (empty($event['event_type_color'])?
                                             self::getPaletteColor('ev', $event['itemtype']):
                                             $event['event_type_color']),
                           'textColor'   => Planning::$palette_fg[$index_color],
                           'typeColor'   => (empty($event['event_type_color'])?
                                             self::getPaletteColor('ev', $event['itemtype']):
                                             $event['event_type_color']),
                           'url'         => isset($event['url'])?$event['url']:"",
                           'ajaxurl'     => isset($event['ajaxurl'])?$event['ajaxurl']:"",
                           'itemtype'    => $event['itemtype'],
                           'parentitemtype'    => isset($event['parentitemtype'])?
                                                   $event['parentitemtype']:"",
                           'items_id'    => $event['id'],
                           'priority'    => isset($event['priority'])?$event['priority']:"",
                           'state'       => isset($event['state'])?$event['state']:""];
      }

      return $events;
   }


   /**
    * construct a single line for self::constructEventsArray()
    * Recursively called to construct $raw_events param.
    *
    * @since 9.1
    *
    * @param  string $actor: a type and id concaneted separated by '_' char, ex 'user_41'
    * @param  array  $params: must contains this keys :
    *  - display: boolean for pass or not the consstruction of this line (a group of users can be displayed but its users not).
    *  - type: event type, can be event_filter, user, group or group_users
    *  - who: integer for identify user
    *  - who_group: integer for identify group
    *  - color: string with #rgb color for event's foreground color.
    *  - event_type_color : string with #rgb color for event's foreground color.
    * @param  array  $raw_events: (passed by reference) the events array in construction
    * @return nothing
    */
   static function constructEventsArraySingleLine($actor, $params = [], &$raw_events = []) {

      if ($params['display']) {
         $actor_array = explode("_", $actor);
         if ($params['type'] == "group_users") {
            $subparams = $params;
            unset($subparams['users']);
            foreach ($params['users'] as $user => $userdata) {
               $subparams = array_merge($subparams, $userdata);
               self::constructEventsArraySingleLine($user, $subparams, $raw_events);
            }
         } else {
            $params['who']       = $actor_array[1];
            $params['who_group'] = 0;
            $params['whogroup']  = 0;
            if ($params['type'] == "group"
                && in_array($params['planning_type'], self::$directgroup_itemtype)) {
               $params['who']       = 0;
               $params['who_group'] = $actor_array[1];
               $params['whogroup']  = $actor_array[1];
            }

            if (isset($params['color'])) {
               $params['color'] = $params['color'];
            }
            $params['event_type_color'] = $params['event_type_color'];
            $current_events = $params['planning_type']::populatePlanning($params);
            if (count($current_events) > 0) {
               $raw_events = array_merge($raw_events, $current_events);
            }
         }
      }
   }


   /**
    * Change dates of a selected event.
    * Called from a drag&drop in planning
    *
    * @since 9.1
    *
    * @param array $options: must contains this keys :
    *  - items_id : integer to identify items
    *  - itemtype : string to identify items
    *  - begin : planning start .
    *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
    *  - end : planning end .
    *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
    * @return bool
    */
   static function updateEventTimes($params = []) {
      if ($item = getItemForItemtype($params['itemtype'])) {
         $params['start'] = date("Y-m-d H:i:s", strtotime($params['start']));
         $params['end']   = date("Y-m-d H:i:s", strtotime($params['end']));

         if ($item->getFromDB($params['items_id'])
          && empty($item->fields['is_deleted'])
         ) {
            // item exists and is not in bin

            $abort = false;

            if (!empty($item->fields['tickets_id'])) {
               // todo: to same checks for changes, problems, projects and maybe reminders and others depending on incoming itemtypes
               $ticket = new Ticket();

               if (!$ticket->getFromDB($item->fields['tickets_id'])
               || $ticket->fields['is_deleted']
               || $ticket->fields['status'] == CommonITILObject::CLOSED
               ) {
                  $abort = true;
               }
            }

            if (!$abort) {
               $update = ['id'   => $params['items_id'],
                           'plan' => ['begin' => $params['start'],
                                           'end'   => $params['end']]];

               if (isset($item->fields['users_id_tech'])) {
                  $update['users_id_tech'] = $item->fields['users_id_tech'];
               }

               if (is_subclass_of($item, "CommonITILTask")) {
                  $parentitemtype = $item->getItilObjectItemType();
                  if (!$update["_job"] = getItemForItemtype($parentitemtype)) {
                     return;
                  }

                  $fkfield = $update["_job"]->getForeignKeyField();
                  $update[$fkfield] = $item->fields[$fkfield];
               }

               return $item->update($update);
            }
         }
      }
   }



   /**
    * Display a Planning Item
    *
    * @param $val       Array of the item to display
    * @param $who             ID of the user (0 if all)
    * @param $type            position of the item in the time block (in, through, begin or end)
    *                         (default '')
    * @param $complete        complete display (more details) (default 0)
    *
    * @return Nothing (display function)
   **/
   static function displayPlanningItem(array $val, $who, $type = "", $complete = 0) {
      global $CFG_GLPI;

      $html = "";

      /*$color = "#e4e4e4";
      if (isset($val["state"])) {
         switch ($val["state"]) {
            case 0 :
               $color = "#efefe7"; // Information
               break;

            case 1 :
               $color = "#fbfbfb"; // To be done
               break;

            case 2 :
               $color = "#e7e7e2"; // Done
               break;
         }
      }*/

      // Plugins case
      if (isset($val['itemtype']) && !empty($val['itemtype'])) {
         $html.= $val['itemtype']::displayPlanningItem($val, $who, $type, $complete);
      }

      return $html;
   }


   /**
    * Display an integer using 2 digits
    *
    * @param $time value to display
    *
    * @return string return the 2 digits item
   **/
   static private function displayUsingTwoDigits($time) {

      $time = round($time);
      if (($time < 10) && (strlen($time) > 0)) {
         return "0".$time;
      }
      return $time;
   }


   /**
    * Show the planning for the central page of a user
    *
    * @param $who ID of the user
    *
    * @return Nothing (display function)
   **/
   static function showCentral($who) {
      global $CFG_GLPI;

      if (!Session::haveRight(self::$rightname, self::READMY)
          || ($who <= 0)) {
         return false;
      }

      echo "<table class='tab_cadrehov'>";
      echo "<tr class='noHover'><th>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/planning.php'>".__('Your planning')."</a>";
      echo "</th></tr>";

      echo "<tr class='noHover'>";
      echo "<td class='planning_on_central'>";
      self::showPlanning(false);
      echo "</td></tr>";
      echo "</table>";
   }



   //*******************************************************************************************************************************
   // *********************************** Implementation ICAL ***************************************************************
   //*******************************************************************************************************************************

   /**
    *  Generate ical file content
    *
    * @param $who             user ID
    * @param $who_group       group ID
    * @param $limititemtype   itemtype only display this itemtype (default '')
    *
    * @return icalendar string
   **/
   static function generateIcal($who, $who_group, $limititemtype = '') {
      global $CFG_GLPI;

      if (($who === 0)
          && ($who_group === 0)) {
         return false;
      }

      if (!empty( $CFG_GLPI["version"])) {
         $unique_id = "GLPI-Planning-".trim($CFG_GLPI["version"]);
      } else {
         $unique_id = "GLPI-Planning-UnknownVersion";
      }

      // create vcalendar
      $vcalendar = new VObject\Component\VCalendar();

      // $xprops = array( "X-LIC-LOCATION" => $tz );
      // iCalUtilityFunctions::createTimezone( $v, $tz, $xprops );

      $interv = [];
      $begin  = time()-MONTH_TIMESTAMP*12;
      $end    = time()+MONTH_TIMESTAMP*12;
      $begin  = date("Y-m-d H:i:s", $begin);
      $end    = date("Y-m-d H:i:s", $end);
      $params = ['genical'   => true,
                      'who'       => $who,
                      'who_group' => $who_group,
                      'whogroup'  => $who_group,
                      'begin'     => $begin,
                      'end'       => $end];

      $interv = [];
      if (empty($limititemtype)) {
         foreach ($CFG_GLPI['planning_types'] as $itemtype) {
            $interv = array_merge($interv, $itemtype::populatePlanning($params));
         }
      } else {
         $interv = $limititemtype::populatePlanning($params);
      }

      if (count($interv) > 0) {
         foreach ($interv as $key => $val) {
            if (isset($val['itemtype'])) {
               if (isset($val[getForeignKeyFieldForItemType($val['itemtype'])])) {
                  $uid = $val['itemtype']."#".$val[getForeignKeyFieldForItemType($val['itemtype'])];
               } else {
                  $uid = "Other#".$key;
               }
            } else {
               $uid = "Other#".$key;
            }

            $vevent['UID']     = $uid;

            $dateBegin = new DateTime($val["begin"]);
            $dateBegin->setTimeZone(new DateTimeZone('UTC'));

            $dateEnd = new DateTime($val["end"]);
            $dateEnd->setTimeZone(new DateTimeZone('UTC'));

            $vevent['DTSTART'] = $dateBegin;
            $vevent['DTEND']   = $dateEnd;

            if (isset($val["tickets_id"])) {
               $summary = sprintf(__('Ticket #%1$s %2$s'), $val["tickets_id"], $val["name"]);
            } else if (isset($val["name"])) {
               $summary = $val["name"];
            }
            $vevent['SUMMARY'] = $summary;

            if (isset($val["content"])) {
               $description = $val["content"];
               // be sure to replace nl by \r\n
               $description = preg_replace("/<br( [^>]*)?".">/i", "\r\n", $description);
               $description = Html::clean($description);
            } else if (isset($val["text"])) {
               $description = $val["text"];
               // be sure to replace nl by \r\n
               $description = preg_replace("/<br( [^>]*)?".">/i", "\r\n", $description);
               $description = Html::clean($description);
            } else if (isset($val["name"])) {
               $description = $val["name"];
               // be sure to replace nl by \r\n
               $description = preg_replace("/<br( [^>]*)?".">/i", "\r\n", $description);
               $description = Html::clean($description);
            }
            $vevent['DESCRIPTION'] = $description;

            if (isset($val["url"])) {
               $vevent['URL'] = $val["url"];
            }
            $vcalendar->add('VEVENT', $vevent);
         }
      }

      $output   = $vcalendar->serialize();
      $filename = date( 'YmdHis' ).'.ics';

      @Header("Content-Disposition: attachment; filename=\"$filename\"");
      //@Header("Content-Length: ".Toolbox::strlen($output));
      @Header("Connection: close");
      @Header("content-type: text/calendar; charset=utf-8");

      echo $output;
   }

   /**
    * @since 0.85
   **/
   function getRights($interface = 'central') {

      $values[self::READMY]    = __('See personnal planning');
      $values[self::READGROUP] = __('See schedule of people in my groups');
      $values[self::READALL]   = __('See all plannings');

      return $values;
   }
}
