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


   static function getForbiddenActionsForMenu() {
      return ['add'];
   }

   static function getTypeName($nb = 0)
   {
      return _n('Event', 'Events', $nb);
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
      // Process event business rules. Only used for correlation, notifications, and tracking
      $rules = new RuleITILEventCollection();
      $input = $rules->processAllRules($this->fields, $this->fields, ['recursive' => true], ['condition' => RuleITILEvent::ONADD]);
      $input = Toolbox::stripslashes_deep($input);

      // If no correlation UUID is assigned from rules, create a new UUID
      if (!isset($input['correlation_uuid'])) {
         $input['correlation_uuid'] = uniqid();
      }

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

   public static function showDashboard($cards_only = false)
   {
      $default_view = [
         'count-alerts-timerange' => ['timeunit' => isset($_GET['_timerange']) ? $_GET['_timerange'] : HOUR_TIMESTAMP],
         'count-active-warnings' => [],
         'count-active-exceptions' => [],
         'count-new' => [],
         'count-remediating' => [],
         'list-historical' => [
            'timeunit' => isset($_GET['_timerange']) ? $_GET['_timerange'] : HOUR_TIMESTAMP,
            'colspan' => 'var(--colcount)']
      ];
      if (!$cards_only) {
         echo "<h2 class='center'>".__('Event Management Dashboard')."</h2>";
         self::showDashboardToolbar();
      }
      echo "<div id='siem-dashboard'>";
      foreach ($default_view as $cardname => $card_params) {
         if (!is_null($card_params) && is_array($card_params)) {
            echo self::getDashboardCard($cardname, $card_params);
         } else {
            echo self::getDashboardCard($cardname);
         }
      }
      echo "</div>";
   }

   private static function showDashboardToolbar()
   {
      global $CFG_GLPI;

      echo "<form id='siem-dashboard-toolbar' class='tab_bg_3'>";
      echo "<div class='siem-dashboard-options'>";

      echo "<span><label for='_timerange'>".__('Time range')."</label>";
      $ajax_url = $CFG_GLPI['root_doc']."/ajax/siemdashboard.php";
      Dropdown::showTimeStamp('_timerange', [
         'value'     => isset($_GET['_timerange']) ? $_GET['_timerange'] : HOUR_TIMESTAMP,
         'on_change' => "refreshDashboard(\"{$ajax_url}\");"
      ]);
      echo "</span>";

      echo "<span><a href='#' class='fa fa-wrench' title='Configure dashboard'>";
      echo "<span class='sr-only'>" . __('Configure dashboard')  . "</span>";
      echo "</a></span>";

      echo "</div></form>";
   }

   public static function getDashboardCardTitle(string $cardname)
   {
      switch ($cardname) {
         case 'count-alerts-timerange':
            $timerange = isset($_GET['_timerange']) ? $_GET['_timerange'] : HOUR_TIMESTAMP;
            $timeunits = Toolbox::getTimestampTimeUnits($timerange);
            $time_string = '';
            if ($timeunits['day'] > 0) {
               $time_string .= " {$timeunits['day']} "._n('Day', 'Days', $timeunits['day']);
            }
            if ($timeunits['hour'] > 0) {
               $time_string .= " {$timeunits['hour']} "._n('Hour', 'Hours', $timeunits['hour']);
            }
            if ($timeunits['minute'] > 0) {
               $time_string .= " {$timeunits['minute']} "._n('Minute', 'Minutes', $timeunits['minute']);
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

   public static function getDashboardCard(string $cardname, array $params = [])
   {
      global $DB;

      $p = [
         'colspan'   => 1,
         'rowspan'   => 1,
         'timeunit'  => HOUR_TIMESTAMP
      ];
      $p = array_replace($p, $params);

      if ($p['timeunit'] > DAY_TIMESTAMP) {
         $p['timeunit'] = DAY_TIMESTAMP;
      }

      $global_where = [new \QueryExpression("date > DATE_ADD(now(), INTERVAL -{$p['timeunit']} SECOND)")];

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

      $title = self::getDashboardCardTitle($cardname);
      $style = '';
      if ((is_numeric($p['colspan']) && $p['colspan'] > 1) ||
            preg_match('/(var\(--)((\w*))[\)]/', $p['colspan'])) {
         $style .= '--colspan:'.$p['colspan'];
      }
      if ((is_numeric($p['rowspan']) && $p['rowspan'] > 1) ||
            preg_match('/(var\(--)((\w*))[\)]/', $p['rowspan'])) {
         $style .= '--rowspan:'.$p['rowspan'];
      }

      $out = '';
      $out .= "<div class='siem-dashboard-card' style='{$style}'><h3>{$title}</h3><div class='card-content'>";
      switch ($cardname) {
         case 'count-alerts-timerange':
            $out .= "<p>".$counters['timerange_alerts']."</p>";
            break;
         case 'count-active-warnings':
            $out .= "<p>".$counters['warning']."</p>";
            break;
         case 'count-active-exceptions':
            $out .= "<p>".$counters['exception']."</p>";
            break;
         case 'count-new':
            $out .= "<p>".$counters['new']."</p>";
            break;
         case 'count-remediating':
            $out .= "<p>".$counters['remediating']."</p>";
            break;
         case 'list-historical':
            $out .= self::showList(false, null, false, $global_where);
            break;
         default:
            $out .= "<p>".__("Invalid dashboard card")."</p>";
      }
      $out .= "</div></div>";
      echo $out;
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
      $header = "<tr><th>".__('ID')."</th>";
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
            if ($data['significance'] == ITILEvent::WARNING) {
               $style = "style='background-color: {$_SESSION['glpieventwarning_color']}'";
            } else if ($data['significance'] == ITILEvent::EXCEPTION) {
               $style = "style='background-color: {$_SESSION['glpieventexception_color']}'";
            }
            $out .= "<tr class='tab_bg_2' $style>";
            $out .= "<td class='center'>".$data['id']."</td>";
            $out .= "<td class='center'>".$data['name']."</td>";
            //echo "<td class='center'>".substr(nl2br($data['content']), 0, 100)."</td>";
            $out .= "<td class='center'>".ITILEvent::getSignificanceName($data['significance'])."</td>";
            $out .= "<td class='center'>".Html::convDateTime($data['date'])."</td>";
            $out .= "<td class='center'>".ITILEvent::getStatusName($data['status'])."</td>";
            $out .= "<td class='center'>".ITILEventCategory::getCategoryName($data['itileventcategories_id'])."</td>";
            $out .= "<td class='center'>".$data['correlation_uuid']."</td>";
            $out .= "</tr>\n";
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

   public static function showList($activeonly = false, CommonDBTM $item = null, $display = true, $where = [])
   {

      $out = '';
      $header_text = $activeonly ? __('Active events') : __('Historical events');
      $selftable = self::getTable();

      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }
      $sql_filters = self::convertFiltersValuesToSqlCriteria(isset($_GET['listfilters']) ? $_GET['listfilters'] : []);
      if ($activeonly) {
         $sql_filters['status'] = self::getActiveStatusArray();
         $sql_filters['NOT']['significance'] = self::INFORMATION;
      }
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
      $header = "<tr><th>".__('ID')."</th>";
      $header .= "<th>".__('Name')."</th>";
      $header .= "<th>".__('Significance')."</th>";
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
            if ($data['significance'] == ITILEvent::WARNING) {
               $style = "style='background-color: {$_SESSION['glpieventwarning_color']}'";
            } else if ($data['significance'] == ITILEvent::EXCEPTION) {
               $style = "style='background-color: {$_SESSION['glpieventexception_color']}'";
            }
            $out .= "<tr id='itilevent_{$data['id']}' class='tab_bg_2' $style onclick='toggleEventDetails(this);'>";
            $out .= "<td class='center'>".$data['id']."</td>";
            $out .= "<td class='center'>".$data['name']."</td>";
            //echo "<td class='center'>".substr(nl2br($data['content']), 0, 100)."</td>";
            $out .= "<td class='center'>".ITILEvent::getSignificanceName($data['significance'])."</td>";
            $out .= "<td class='center'>".Html::convDateTime($data['date'])."</td>";
            $out .= "<td class='center'>".ITILEvent::getStatusName($data['status'])."</td>";
            $out .= "<td class='center'>".ITILEventCategory::getCategoryName($data['itileventcategories_id'])."</td>";
            $out .= "<td class='center'>".$data['correlation_uuid']."</td>";
            $out .= "</tr>\n";

            $out .= "<tr id='itilevent_{$data['id']}_content' class='tab_bg_2' $style hidden='hidden'>";
            $out .= "<td colspan='{$colcount}'>";
            $content = '';
            $content_json = self::getEventProperties($data['content'], $data['logger']);
            foreach ($content_json as $property) {
               $content .= "<p>{$property['name']}: {$property['value']}</p>";
            }
            $out .= $content;
            $out .= "</td></tr>\n";
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

   /**
    * Convert filters values into SQL filters usable in 'WHERE' condition of request build with 'DBmysqlIterator'.
    *
    * @param array $filters  Filters values
    * @return array
    *
    * @since 10.0.0
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
    * @param bool $exclusive True if the results should not include this event
    * @return DBmysqlIterator
    */
   public function getCorrelated(bool $exclusive = false)
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
    * Update all events with the same correlation UUID (exclusive)
    * 
    * @param array $params Query parameters ([:field name => field value)
    * @param array $where  WHERE clause
    */
   public function updateCorrelated(array $params, array $where = [])
   {
      global $DB;

      $where = [
         'NOT' => [
            'id' => $this->getID()
         ],
         'correlation_uuid' => $this->fields['correlation_uuid']
      ] + $where;

      $DB->update(self::getTable(), $params, $where);
   }

   /**
    * 
    * @param string $name
    * @param string $logger
    * @return string
    */
   public static function getLocalizedEventName(string $name, $logger) {
      if ($logger !== null) {
         if (file_exists(GLPI_ROOT . "/plugins/$logger/hook.php")) {
            include_once(GLPI_ROOT . "/plugins/$logger/hook.php");
         }
         if (is_callable('translateEventName')) {
            return call_user_func('translateEventName', $name);
         }
      }
      return $name;
   }

   /**
    * Get an associative array of event properties from the content JSON field
    * 
    * @param boolean $translate Attempt to translate the event properties.
    * @return array Associative array of event properties
    * @since 10.0.0
    */
   public static function getEventProperties(string $content, $logger, bool $translate = true)
   {
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

      if ($translate) {
         if ($logger !== null) {
            if (file_exists(GLPI_ROOT . "/plugins/$logger/hook.php")) {
               include_once(GLPI_ROOT . "/plugins/$logger/hook.php");
            }
            if (is_callable('translateEventProperties')) {
               call_user_func('translateEventProperties', $props);
            }
         } else {
            Glpi\Event::translateEventProperties($props);
         }
      }

      return $props;
   }
}