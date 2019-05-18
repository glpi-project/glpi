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

/**
 * ITILEvent class
 * @since 10.0.0
 */
class ITILEvent extends CommonDBTM
{

   /**
    * An event that doesn't require any response
    */
   const INFORMATION = 0;

   /**
    * An event that indicates a potential issue that requires monitoring or preventative measures.
    */
   const WARNING = 1;

   /**
    * An event that indicates an issue and requires a response.
    */
   const EXCEPTION = 2;

   //TODO Handle status workflows as described by each status.
   // Try to factorize the workflow as much as possible to make it easy to replace in the future.

   /**
    * An event that was logged but not acted on. For informational alerts, this is the only valid status.
    */
   const STATUS_NEW = 0;

   /**
    * An event that has been acknowledged by a technician. Duplicate alerts should be dropped for a period of time.
    */
   const STATUS_ACKNOWLEDGED = 1;

   /**
    * An event that is currently being remediated by a technician or automatically.
    * Similar to acknowledged events, duplicate events are dropped
    */
   const STATUS_REMEDIATING = 2;

   /**
    * An event that may be resolved. The event will be considered resolved after a time.
    * If a duplicate event is logged, it is linked and this event is downgraded to acknowledged.
    */
   const STATUS_MONITORING = 3;

   /**
    * An event that has been determined to be resolved either manually or automatically after a time period.
    * If a duplicate alert comes in, it is treated as a new event and not linked.
    */
   const STATUS_RESOLVED = 4;

   /**
    * An event that went so long without being resolved that another event has replaced it through correlation rules or a timeout period.
    * This event will be linked to the replacement event if one exists.
    * If there is no replacement (timeout), then new events will not be linked.
    */
   const STATUS_EXPIRED = 5;

   static $rightname                = 'event';


   static function getTypeName($nb = 0)
   {
      return _n('Event', 'Events', $nb);
   }

   static function getForbiddenActionsForMenu() {
      return ['add'];
   }

   function prepareInputForAdd($input)
   {
      $input = parent::prepareInputForAdd($input);

      if (isset($input['content']) && !is_string($input['content'])) {
         $input['content'] = json_encode($input['content']);
      }

      if ($input['significance'] < 0 || $input['significance'] > 2) {
         $input['significance'] = self::INFORMATION;
      }

      // Process event filtering rules
      $rules = new RuleITILEventFilterCollection();

      $input['_accept'] = true;
      $input = $rules->processAllRules($input,
                                       $input,
                                       ['recursive' => true],
                                       ['condition' => RuleITILEvent::ONADD]);
      $input = Toolbox::stripslashes_deep($input);

      if (!$input['_accept']) {
         // Drop the event
         return false;
      } else {
         return $input;
      }
   }

   function post_addItem()
   {
      // Associate items
      if (isset($this->input['_items'])) {
         $item_itilevent = new Item_ITILEvent();
         foreach ($this->input['_items'] as $item) {
            $item_itilevent->add([
               'itilevents_id'   => $this->getID(),
               'itemtype'        => $item['itemtype'],
               'items_id'        => $item['items_id'],
               'link'            => isset($item['link']) ? $item['link'] : Item_ITILEvent::LINK_SOURCE
            ]);
         }
      }

      if (!isset($this->input['correlation_uuid']) && !isset($this->fields['correlation_uuid'])) {
         // Create a new correlation UUID in case one isn't assigned by the correlation engine
         $this->fields['correlation_uuid'] = uniqid();
      }

      $this->update([
         'id'                 => $this->getID(),
         'correlation_uuid'   => $this->fields['correlation_uuid']
      ]);

      // Process event business rules. Only used for correlation, notifications, and tracking
      $rules = new RuleITILEventCollection();
      $input = $rules->processAllRules($this->fields, $this->fields, ['recursive' => true], ['condition' => RuleITILEvent::ONADD]);
      $input = Toolbox::stripslashes_deep($input);

      $this->update([
         'id' => $this->getID()
      ] + $input);
      parent::post_addItem();
   }

   function cleanDBonPurge()
   {
      $this->deleteChildrenAndRelationsFromDb(
         [
            Item_ITILEvent::class
         ]
      );

         parent::cleanDBonPurge();
   }

   /**
    * Gets the name of a significance level from the int value
    *
    * @since 10.0.0
    *
    * @param int $significance The significance level
    *
    * @return string The significance level name
    */
   static function getSignificanceName($significance)
   {
      switch ($significance) {
         case 1:
            return __('Warning');
         case 2:
            return __('Exception');
         case 0:
         default:
            return __('Information');
      }
   }

   /**
    * Displays or gets a dropdown menu of significance levels.
    * The default functionality is to display the dropdown.
    *
    * @since 10.0.0
    *
    * @param array $options Dropdown options
    *
    * @return void|string
    * @see Dropdown::showFromArray()
    */
   static function dropdownSignificance(array $options = [])
   {
      global $CFG_GLPI;

      $p = [
         'name'     => 'significance',
         'value'    => 0,
         'showtype' => 'normal',
         'display'  => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $values = [];
      $values[0] = self::getSignificanceName(0);
      $values[1] = self::getSignificanceName(1);
      $values[2] = self::getSignificanceName(2);

      return Dropdown::showFromArray($p['name'], $values, $p);
   }

   /**
    * Gets the name of an event status from the int value
    *
    * @since 10.0.0
    *
    * @param int $status The event status
    *
    * @return string The event status name
    */
   static function getStatusName($status) : string
   {
      switch ($status) {
         case 0:
            return __('New');
         case 1:
            return __('Acknowledged');
         case 2:
            return __('Remediating');
         case 3:
            return __('Monitoring');
         case 4:
            return __('Resolved');
         case 5:
            return __('Expired');
         default:
            return __('Unknown');
      }
   }

   /**
    * Displays or gets a dropdown menu of event statuses.
    * The default functionality is to display the dropdown.
    *
    * @since 10.0.0
    *
    * @param array $options Dropdown options
    *
    * @return void|string
    * @see Dropdown::showFromArray()
    */
   static function dropdownStatus(array $options = [])
   {
      global $CFG_GLPI;

      $p = [
         'name'     => 'status',
         'value'    => 0,
         'showtype' => 'normal',
         'display'  => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $values = [];
      $values[0] = self::getStatusName(0);
      $values[1] = self::getStatusName(1);
      $values[2] = self::getStatusName(2);
      $values[3] = self::getStatusName(3);
      $values[4] = self::getStatusName(4);
      $values[5] = self::getStatusName(5);

      return Dropdown::showFromArray($p['name'], $values, $p);
   }

   /**
    * Get an array of statuses that indicate the alert is still active.
    *    By default, this includes New, Acknowledged, and Remediating.
    *
    * @since 10.0.0
    *
    * @return array Array of status integers
    */
   public static function getActiveStatusArray()
   {
      return [self::STATUS_NEW, self::STATUS_ACKNOWLEDGED, self::STATUS_REMEDIATING];
   }

   /**
    * Get event data that matches specific parameters
    *
    * @since 10.0.0
    *
    * @param type $item   An asset or tracking object that the resulting events should be linked to
    * @param int $start   The index of the first result when paginating results
    * @param int $limit   The maximum number of results returned
    * @param array $where An associative array of WHERE filters for the iterator
    * @return \DBmysqlIterator The event data iterator or false if the query failed
    */
   public static function getEventData($item, int $start = 0, int $limit = 0, array $where = []) : DBmysqlIterator
   {
      global $DB;

      $eventtable = self::getTable();

      $query = [
         'FROM' => $eventtable,
         'WHERE' => [],
         'ORDERBY' => ['date DESC']
      ];

      if ($item != null) {
         switch ($item->getType()) {
            case 'Ticket' :
            case 'Change' :
            case 'Problem' :
               $itemeventtable = Itil_ITILEvent::getTable();
            default :
               $itemeventtable = Item_ITILEvent::getTable();
         }
         $query['WHERE'][] = [
            "{$itemeventtable}.itemtype" => $item->getType(),
            "{$itemeventtable}.items_id" => $item->getID()
         ];
         $query['LEFT JOIN'] = [
            $itemeventtable => [
               'FKEY' => [
                  $eventtable       => 'id',
                  $itemeventtable   => 'itilevents_id'
               ]
            ]
         ];
      }
      if (isset($where['category'])) {
         $query['WHERE'][] = [
            "$eventtable.itileventcategories_id" => $where['category']
         ];
         unset($where['category']);
      }
      $query['WHERE'] = array_merge_recursive($query['WHERE'], $where);
      if ($limit) {
         $query['START'] = (int)$start;
         $query['LIMIT'] = (int)$limit;
      }
      $iterator = $DB->request($query);
      return $iterator;
   }

   public static function getDashboardToolbar()
   {
      global $CFG_GLPI;

      $out = "<form id='siem-dashboard-toolbar' class='tab_bg_3'>";
      $out .= "<div class='siem-dashboard-options'>";

      $out .= "<span><label for='_timerange'>".__('Time range')."</label>";
      $ajax_url = $CFG_GLPI['root_doc']."/ajax/siemdashboard.php";
      $out .= Dropdown::showTimeStamp('_timerange', [
         'value'     => isset($_GET['_timerange']) ? $_GET['_timerange'] : HOUR_TIMESTAMP,
         'on_change' => "refreshDashboard(\"{$ajax_url}\");",
         'display'   => false
      ]);
      $out .= "</span>";

      $out .= "<span><a href='#' class='fa fa-filter' title='Filter' onclick='toggleEventFilter();'>";
      $out .= "<span class='sr-only'>" . __('Filter')  . "</span>";
      $out .= "</a></span>";

      $out .= "<span><a href='#' class='fa fa-wrench' title='Configure dashboard'>";
      $out .= "<span class='sr-only'>" . __('Configure dashboard')  . "</span>";
      $out .= "</a></span>";

      $out .= "</div></form>";
      return $out;
   }

   /**
    * Get the title for the specified dashboard card
    *
    * @since 10.0.0
    *
    * @param string $cardname The name of the dashboard card
    * @return string The card title
    */
   public static function getDashboardCardTitle(string $cardname)
   {
      switch ($cardname) {
         case 'count-alerts-timerange':
            $timerange = isset($_GET['_timerange']) ? $_GET['_timerange'] : HOUR_TIMESTAMP;
            $timeunits = Toolbox::getTimestampTimeUnits($timerange);
            $time_string = '';
            if ($timeunits['day'] > 0) {
               $time_string .= sprintf(_n('%d day', '%d days', $timeunits['day']), $timeunits['day']) . ' ';
            }
            if ($timeunits['hour'] > 0) {
               $time_string .= sprintf(_n('%d hour', '%d hour', $timeunits['hour']), $timeunits['hour']) . ' ';
            }
            if ($timeunits['minute'] > 0) {
               $time_string .= sprintf(_n('%d minute', '%d minute', $timeunits['minute']), $timeunits['minute']);
            }
            $time_string = trim($time_string);
            return __('Total Alerts')." ({$time_string})";
         case 'count-active-warnings':
            return __('Active Warnings');
         case 'count-active-exceptions':
            return __('Active Exceptions');
         case 'count-new':
            return __('New Events');
         case 'count-remediating':
            return __('Remediating Events');
         case 'list-historical':
            return __('Historical Events');
         default:
            return $cardname;
      }
   }

   public static function getDashboardCardData(string $cardname, array $params = []) {
      global $DB;

      $card = [
         'header'             => '',
         'extra_card_classes' => '',
         'value'              => '',
         'type'               => 'counter'
      ];
      $p = [
         'colspan'      => 1,
         'rowspan'      => 1,
         'timeunit'     => HOUR_TIMESTAMP,
         'timerange'    => HOUR_TIMESTAMP,
      ];
      $p = array_replace($p, $params);

      if ($p['timerange'] > DAY_TIMESTAMP) {
         $p['timerange'] = DAY_TIMESTAMP;
      }

      $global_where = [new \QueryExpression("date > DATE_ADD(now(), INTERVAL -{$p['timerange']} SECOND)")];

      // Get countable data and cache it (Specific counters without a timeframe)
      $iterator = $DB->request([
         'SELECT' => [
            'id',
            'significance',
            'status'
         ],
         'FROM' => self::getTable(),
         'WHERE' => [
            'status'       => self::getActiveStatusArray(),
            'significance' => [self::WARNING, self::EXCEPTION]
         ]
      ]);

      // Get all event data from a specific timeframe
      // Limited to a maximum of a day, but can be offset to provide data from other days
      $timerange_alerts = $DB->request([
         'COUNT' => 'cpt',
         'FROM' => self::getTable(),
         'WHERE' => [
            'significance' => [self::WARNING, self::EXCEPTION]
         ] + $global_where
      ]);

      static $counters = null;
      if ($counters === null) {
         $counters = array_fill_keys(['warning', 'exception', 'new', 'remediating'], 0);
         $counters['timerange_alerts'] = $timerange_alerts->next()['cpt'];
         while ($data = $iterator->next()) {
            if ($data['significance'] == self::WARNING) {
               $counters['warning'] += 1;
            } else if ($data['significance'] == self::EXCEPTION) {
               $counters['exception'] += 1;
            }
            if ($data['status'] == self::STATUS_NEW) {
               $counters['new'] += 1;
            } else if ($data['status'] == self::STATUS_REMEDIATING) {
               $counters['remediating'] += 1;
            }
         }
      }

      $card['header'] = self::getDashboardCardTitle($cardname);

      switch ($cardname) {
         case 'count-alerts-timerange':
            $card['type'] = 'counter';
            $card['value'] = $counters['timerange_alerts'];
            break;
         case 'count-active-warnings':
            $card['type'] = 'counter';
            $card['extra_card_classes'] = 'bg-warning';
            $card['value'] = $counters['warning'];
            break;
         case 'count-active-exceptions':
            $card['type'] = 'counter';
            $card['extra_card_classes'] = 'bg-danger';
            $card['value'] = $counters['exception'];
            break;
         case 'count-new':
            $card['type'] = 'counter';
            $card['value'] = $counters['new'];
            break;
         case 'count-remediating':
            $card['type'] = 'counter';
            $card['extra_card_classes'] = 'bg-info';
            $card['value'] = $counters['remediating'];
            break;
         case 'list-historical':
            $card['type'] = 'list';
            $card['value'] = self::showList([
               'where'  => $global_where,
               'display'   => false
            ]);
            break;
         case 'timeseries-new':
            break;
         default:
            $card['type'] = 'invalid';
            $card['value'] = __("Invalid dashboard card");
      }
      return $card;
   }

   public static function showListForItem(CommonDBTM $item = null, $display = true, $where = []) {
      $out = '';
      $header_text = __('Historical events');
      $selftable = self::getTable();

      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }
      $sql_filters = self::convertFiltersValuesToSqlCriteria(isset($_GET['listfilters']) ? $_GET['listfilters'] : []);
      $sql_filters = $sql_filters + $where;

      $iterator = ITILEvent::getEventData($item, $start, $_SESSION['glpilist_limit'], $sql_filters);
      
      // Display the pager
      $additional_params = isset($_GET['listfilters']) ? http_build_query(['listfilters' => $_GET['listfilters']]) : '';
      $out .= Html::printAjaxPager($header_text, $start, $iterator->count(), '', false, $additional_params);


      $out .= "<div class='firstbloc'>";

      $out .= "<table class='tab_cadre_fixehov'><tr>";

      //TODO Find a clean way to show associated items in list entry (Useful for dashboard view)
      // Should items be grouped together in the same row, or have some sort of expandable information panel
      // Alternative is to only allow a single item link per Event
      $header = "<tr><th></th>";
      $header .= "<th>".__('Name')."</th>";
      $header .= "<th>".__('Significance')."</th>";
      $header .= "<th>".__('Date')."</th>";
      $header .= "<th>".__('Status')."</th>";
      $header .= "<th>".__('Category')."</th>";
      $header .= "<th>".__('Correlation ID')."</th></tr>";
      $colcount = 7;

      $out .= "<thead>";
      $out .= $header;
      if (isset($_GET['listfilters'])) {
         $out .= "<tr class='log_history_filter_row'>";
         $out .= "<th>";
         $out .= "<input type='hidden' name='listfilters[active]' value='1' />";
         $out .= "<input type='hidden' name='items_id' value='{$item->getID()}' />";
         $out .= "</th>";
         $out .= "<th>";
         $out .= Html::input('listfilters[name]');
         $out .= "</th>";
         $out .= "<th>";
         $out .= ITILEvent::dropdownSignificance([
            'name'                  => 'listfilters[significance]',
            'value'                 => '',
            'values'                => isset($_GET['listfilters']['significance']) ?
                                          $_GET['listfilters']['significance'] : [],
            'multiple'              => true,
            'width'                 => '100%',
            'display'               => false
         ]);
         $out .= "</th>";
         $dateValue = isset($_GET['filters']['date']) ? Html::cleanInputText($_GET['listfilters']['date']) : null;
         $out .= "<th><input type='date' name='listfilters[date]' value='$dateValue' /></th>";
         $out .= "<th>";
         $out .= ITILEvent::dropdownStatus([
            'name'                  => 'listfilters[status]',
            'value'                 => '',
            'values'                => isset($_GET['listfilters']['status']) ?
                                          $_GET['listfilters']['status'] : [],
            'multiple'              => true,
            'width'                 => '100%',
            'display'               => false
         ]);
         $out .= "</th>";
         $out .= "<th>";
         $out .= ITILEventCategory::dropdown([
            'name'                  => 'listfilters[category]',
            'value'                 => '',
            'values'                => isset($_GET['listfilters']['category']) ?
                                          $_GET['listfilters']['category'] : [],
            'multiple'              => true,
            'width'                 => '100%',
            'comments'              => false,
            'display'               => false
         ]);
         $out .= "</th>";
         $out .= "</tr>";
      } else {
         $out .= "<tr>";
         $out .= "<th colspan='{$colcount}'>";
         $out .= "<a href='#' class='show_list_filters'>" . __('Show filters') . " <span class='fa fa-filter pointer'></span></a>";
         $out .= "</th>";
         $out .= "</tr>";
      }
      $out .= "</thead>";

      $out .= "<tfoot>$header</tfoot>";

      if (!count($iterator)) {
         $out .= "<tr class='tab_bg_2'>";
         $out .= "<td class='center' colspan='{$colcount}'>".__('No event')."</td></tr>\n";
      } else {
         $out .= "<tbody>";
         while ($data = $iterator->next()) {
            $style = '';
            $icon = 'fas fa-info-circle';
            $active = in_array($data['status'], self::getActiveStatusArray());
            if ($data['significance'] == ITILEvent::WARNING) {
               if ($active) {
                  $style = "style='background-color: {$_SESSION['glpieventwarning_color']}'";
               }
               $icon = 'fas fa-exclamation-triangle';
            } else if ($data['significance'] == ITILEvent::EXCEPTION) {
               if ($active) {
                  $style = "style='background-color: {$_SESSION['glpieventexception_color']}'";
               }
               $icon = 'fas fa-exclamation-circle';
            }
            $out .= "<tr id='itilevent_{$data['id']}' class='tab_bg_2' $style onclick='toggleEventDetails(this);'>";
            $out .= "<td class='center'><i class='{$icon} fa-lg'/></td>";
            $out .= "<td class='center'>".$data['name']."</td>";
            $out .= "<td class='center'>".ITILEvent::getSignificanceName($data['significance'])."</td>";
            $out .= "<td class='center'>".Html::convDateTime($data['date'])."</td>";
            $out .= "<td class='center'>".ITILEvent::getStatusName($data['status'])."</td>";
            $out .= "<td class='center'>".ITILEventCategory::getCategoryName($data['itileventcategories_id'])."</td>";
            $out .= "<td class='center'>".$data['correlation_uuid']."</td>";
            $out .= "</tr>\n";

            $out .= "<tr id='itilevent_{$data['id']}_content' class='tab_bg_2' $style hidden='hidden'>";
            $out .= "<td colspan='{$colcount}'><p>";
            $content = '';
            $content_json = self::getEventProperties($data['content'], $data['logger']);
            foreach ($content_json as $property) {
               $safename = html_entity_decode($property['name']);
               $safeval = html_entity_decode($property['value']);
               $content .= "{$safename}: {$safeval}<br>";
            }
            $out .= $content;
            $out .= "</p></td></tr>\n";
         }
         $out .= "</tbody>";
      }
      $out .= "</table></div>";
      $out .= Html::printAjaxPager($header_text, $start, $iterator->count(), '', false, $additional_params);
      if (!$display) {
         return $out;
      } else {
         echo $out;
      }
   }

   public static function showList($params)
   {
      global $CFG_GLPI;

      $p = [
         'id'           => 'eventlist'.mt_rand(),
         'display'      => true,
         'where'        => [],
         'list_limit'   => $_SESSION['glpilist_limit'],
         'item'         => null
      ];
      $p = array_replace($p, $params);

      $out = '';
      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }

      $iterator = ITILEvent::getEventData($p['item'], $start, $_SESSION['glpilist_limit'], $p['where']);
      $ajax_url = $CFG_GLPI['root_doc']."/ajax/siemdashboard.php";

      // Display the pager
      $out .= Html::printAjaxPager('', $start, $iterator->count(), '', false);

      $out .= "<div class='firstbloc'>";

      $out .= "<table class='tab_cadre_fixehov'><tr>";

      //TODO Find a clean way to show associated items in list entry (Useful for dashboard view)
      // Should items be grouped together in the same row, or have some sort of expandable information panel
      // Alternative is to only allow a single item link per Event
      $header = "<tr><th></th>";
      $header .= "<th>".__('Name')."</th>";
      $header .= "<th>".__('Date')."</th>";
      $header .= "<th>".__('Status')."</th>";
      $header .= "<th>".__('Category')."</th>";
      $header .= "<th>".__('Correlation ID')."</th></tr>";
      $colcount = 8;

      $out .= "<thead>$header</thead>";
      $out .= "<tfoot>$header</tfoot>";

      if (!count($iterator)) {
         $out .= "<tr class='tab_bg_2'>";
         $out .= "<td class='center' colspan='{$colcount}'>".__('No event')."</td></tr>\n";
      } else {
         $out .= "<tbody>";
         while ($data = $iterator->next()) {
            $style = '';
            $icon = 'fas fa-info-circle';
            $active = in_array($data['status'], self::getActiveStatusArray());
            if ($data['significance'] == ITILEvent::WARNING) {
               if ($active) {
                  $style = "style='background-color: {$_SESSION['glpieventwarning_color']}'";
               }
               $icon = 'fas fa-exclamation-triangle';
            } else if ($data['significance'] == ITILEvent::EXCEPTION) {
               if ($active) {
                  $style = "style='background-color: {$_SESSION['glpieventexception_color']}'";
               }
               $icon = 'fas fa-exclamation-circle';
            }
            $out .= "<tr id='itilevent_{$data['id']}' class='tab_bg_2' $style onclick='toggleEventDetails(this);'>";
            $out .= "<td class='center'><i class='{$icon} fa-lg' title='".
                  ITILEvent::getSignificanceName($data['significance'])."'/></td>";
            $out .= "<td class='center'>".$data['name']."</td>";
            $out .= "<td class='center'><time>".Html::convDateTime($data['date'], null, true)."</time></td>";
            $out .= "<td class='center'>".ITILEvent::getStatusName($data['status'])."</td>";
            $out .= "<td class='center'>".ITILEventCategory::getCategoryName($data['itileventcategories_id'])."</td>";
            $out .= "<td class='center'>".$data['correlation_uuid']."</td>";
            $out .= "</tr>\n";

            $out .= "<tr id='itilevent_{$data['id']}_content' class='tab_bg_2' $style hidden='hidden'>";
            $out .= "<td colspan='{$colcount}'><p>";
            $out .= self::getEventProperties($data['content'], $data['logger'], [
               'format' => 'pretty'
            ]);
            $out .= "</p></td></tr>\n";
         }
         $out .= "</tbody>";
      }
      $out .= "</table></div>";
      $out .= Html::printAjaxPager('', $start, $iterator->count(), '', false);
      if (!$p['display']) {
         return $out;
      } else {
         echo $out;
      }
   }

   /**
    * Convert filters values into SQL filters usable in 'WHERE' condition of request build with 'DBmysqlIterator'.
    *
    * @since 10.0.0
    *
    * @param array $filters  Filters values
    * @return array
    **/
   static function convertFiltersValuesToSqlCriteria(array $filters)
   {
      $sql_filters = [];

      if (isset($filters['name']) && !empty($filters['name'])) {
         $sql_filters['name'] = ['LIKE', "%{$filters['name']}%"];
      }

      if (isset($filters['date']) && !empty($filters['date'])) {
         $sql_filters['date_mod'] = ['LIKE', "%{$filters['date']}%"];
      }

      if (isset($filters['status']) && !empty($filters['status'])) {
         $sql_filters['status'] = $filters['status'];
      }

      if (isset($filters['significance']) && !empty($filters['significance'])) {
         $sql_filters['significance'] = $filters['significance'];
      }

      if (isset($filters['category']) && !empty($filters['category'])) {
         $sql_filters['category'] = $filters['category'];
      }

      return $sql_filters;
   }

   /**
    * Gets all events with the same correlation UUID as this event
    *
    * @since 10.0.0
    *
    * @param bool $exclusive True if the results should not include this event
    * @return DBmysqlIterator
    */
   public function getCorrelated(bool $exclusive = true)
   {
      global $DB;
      $query = [
         'FROM' => self::getTable(),
         'WHERE' => [
            'correlation_uuid' => $this->fields['correlation_uuid']
         ]
      ];
      if ($exclusive) {
         $query['WHERE'][] = [
            'NOT' => ['id' => $this->getID()]
         ];
      }
      return $DB->request($query);
   }

   /**
    * Update all events with the same correlation UUID
    *
    * @since 10.0.0
    *
    * @param array $params Query parameters ([:field name => field value)
    * @param array $where  WHERE clause
    * @param bool $exclusive True if this event should also be updated
    * @return PDOStatement|boolean
    */
   public function updateCorrelated(array $params, array $where = [], bool $exclusive = true)
   {
      global $DB;

      $where = [
         'NOT' => [
            'id' => $this->getID()
         ],
         'correlation_uuid' => $this->fields['correlation_uuid']
      ] + $where;

      if ($exclusive) {
         $where[] = [
            'NOT' => ['id' => $this->getID()]
         ];
      }

      return $DB->update(self::getTable(), $params, $where);
   }

   /**
    * Gets the translated event name from the event's logger (GLPI or plugin)
    *
    * @since 10.0.0
    *
    * @param string $name   The unlocalized event name
    * @param string $logger The plugin that created the event or null if made by GLPI
    * @return string The localized name if possible, otherwise the unlocalized name is returned
    */
   public static function getLocalizedEventName(string $name, $logger) {
      if ($logger !== null) {
         return Plugin::doOneHook($logger, 'translateEventName', $name);
      }
      return $name;
   }

   /**
    * Get an associative array of event properties from the content JSON field
    *
    * @since 10.0.0
    *
    * @param boolean $translate Attempt to translate the event properties.
    * @return array|string Associative array or HTML display of event properties
    */
   public static function getEventProperties(string $content, $logger, array $params = [])
   {
      $p = [
         'translate' => true,
         'format'    => 'array'
      ];
      $p = array_replace($p, $params);

      if (!in_array($p['format'], ['array', 'pretty', 'plain'])) {
         $p['format'] = 'array';
      }

      if ($content !== null) {
         $properties = json_decode($content, true);
      } else {
         return [];
      }
      if ($properties == null) {
         return [];
      }

      $props = [];
      foreach ($properties as $key => $value) {
         $props[$key] = [
            'name'   => $key, // Potentially localized property name
            'value'  => $value // Property value
         ];
      }

      if ($p['translate']) {
         if ($logger !== null) {
            $props = Plugin::doOneHook($logger, 'translateEventProperties', $props);
         } else {
            Glpi\Event::translateEventProperties($props);
         }
      }

      if ($p['format'] == 'array') {
         return $props;
      } else {
         $text_content = '';
         foreach ($props as $event_property) {
            $propname = strip_tags($event_property['name']);
            $propvalue = strip_tags($event_property['value']);

            if ($p['format'] == 'pretty') {
               $text_content .= "<b>{$propname}</b>: {$propvalue}<br>";
            } else {
               $text_content .= "{$propname}: {$propvalue}<br>";
            }
         }
         return $text_content;
      }
   }

   /**
    * Create a ticket, change, or problem from this event
    *
    * @since 10.0.0
    *
    * @param string $tracking_type  The tracking class (Ticket, Change, or Problem)
    * @return boolean True if the tracking was created successfully
    */
   public function createTracking(string $tracking_type)
   {
      global $DB;

      if (is_subclass_of($tracking_type, 'CommonITILObject')) {
         $tracking = new $tracking_type();

         $content = ITILEvent::getEventProperties($this->fields['content'], $this->fields['logger'], [
            'format' => 'plain'
         ]);

         $tracking_id = $tracking->add([
            'name'               => $this->fields['name'],
            'content'            => $content,
            '_correlation_uuid'  => $this->fields['correlation_uuid']
         ]);

         if (!$tracking_id) {
            return false;
         } else {
            // Add related items if they exist
            if ($tracking_type == 'Change') {
               $items_tracking = 'glpi_items_changes';
            } else if ($tracking_type == 'Problem') {
               $items_tracking = 'glpi_items_problems';
            } else {
               $items_tracking = 'glpi_items_tickets';
            }
            $iterator = $DB->request([
               'SELECT' => ['itemtype', 'items_id'],
               'FROM'   => Item_ITILEvent::getTable(),
               'WHERE'  => [
                  'itilevents_id'   => $this->getID()
               ]
            ]);

            $actors_responsible = ['user' => [], 'group' => []];
            while ($data = $iterator->next()) {
               $DB->insert($items_tracking, [
                  'itemtype'                       => $data['itemtype'],
                  'items_id'                       => $data['items_id'],
                  $tracking::getForeignKeyField()  => $tracking_id
               ]);
               // Get responsible tech and group
               $actors = $DB->request([
                  'SELECT' => ['users_id_tech', 'groups_id_tech'],
                  'FROM'   => $data['itemtype']::getTable(),
                  'WHERE'  => ['id' => $data['items_id']]
               ])->next();
               if (!is_null($actors['users_id_tech'])) {
                  $actors_responsible['user'][] = $actors['users_id_tech'];
               }
               if (!is_null($actors['groups_id_tech'])) {
                  $actors_responsible['group'][] = $actors['groups_id_tech'];
               }
            }

            // Assign responsible actors
            // TODO Respect Entity assignment settings?
            $tracking_user = new $tracking->userlinkclass();
            $tracking_group = new $tracking->grouplinkclass();
            foreach ($actors_responsible as $type => $actor_id) {
               if ($type == 'user') {
                  $tracking_user->add([
                     'type'                           => CommonITILActor::ASSIGN,
                     'users_id'                       => $actor_id[0],
                     $tracking::getForeignKeyField()  => $tracking_id
                  ]);
               } else if ($type == 'group') {
                  $tracking_group->add([
                     'type'                           => CommonITILActor::ASSIGN,
                     'groups_id'                      => $actor_id[0],
                     $tracking::getForeignKeyField()  => $tracking_id
                  ]);
               }
            }

            $itil_itilevent = new Itil_ITILEvent();
            $itil_itilevent->add([
               'itemtype'        => $tracking_type,
               'items_id'        => $tracking_id,
               'itilevents_id'   => $this->getID()
            ]);
         }
      } else {
         Toolbox::logError(__("Tracking type must be a subclass of CommonITILObject"));
         return false;
      }
   }

   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'significance',
         'name'               => __('Significance'),
         'datatype'           => 'specific',
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'correlation_uuid',
         'name'               => __('Correlation ID'),
         'datatype'           => 'string',
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'logger',
         'name'               => __('Logger'),
         'datatype'           => 'string',
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => 'glpi_itileventcategories',
         'field'              => 'completename',
         'name'               => __('Category'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'content',
         'name'               => __('Content'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => $this->getTable(),
         'field'              => 'status',
         'name'               => __('Status'),
         'datatype'           => 'specific',
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      return $tab;
   }
}
