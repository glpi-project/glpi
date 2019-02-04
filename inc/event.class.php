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

namespace Glpi;

use \Ajax;
use \CommonDBTM;
use \Html;
use \Session;
use \Toolbox;
use \Infocom;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Event Class
 * Internal event logging for GLPI services.
 * 
 * Starting in 10.0.0, any event added using this class is translated and added using ITILEvent.
 * This class remains to enforce normalization of internal event data but the glpi_events table is not used.
**/
class Event extends CommonDBTM {

   static $rightname = 'logs';



   static function getTypeName($nb = 0) {
      return _n('Log', 'Logs', $nb);
   }

   function prepareInputForAdd($input) {

      Toolbox::deprecated("Events should be logged using the log function since version 10.0.0");
      // Deny any attempt to add an event to the legacy table
      return false;
   }

   function post_addItem() {
      //TODO For the sake of consistancy, logging alerts to a file should be dropped.
      // Maybe add it as a rule action instead?
      if (isset($this->fields['level']) && $this->fields['level'] <= 3) {
         $message_type = "";
         if (isset($this->fields['type']) && $this->fields['type'] != 'system') {
            $message_type = "[".$this->fields['type']." ".$this->fields['id']."] ";
         }

         $full_message = "[".$this->fields['service']."] ".
                         $message_type.
                         $this->fields['level'].": ".
                         $this->fields['message']."\n";

         Toolbox::logInFile("event", $full_message);
      }
   }


   /**
    * Log an event.
    *
    * Log the event $event to the internal SIEM system as an {@link \ITILEvent} if
    * $level is above or equal to setting from configuration.
    *
    * @param $items_id
    * @param $type
    * @param $level
    * @param $service
    * @param $event
    * @param $significance int The significance of the event (0 = Information, 1 = Warning, 2 = Exception).
    *    Default is Information.
   **/
   static function log($items_id, $type, $level, $service, $event, int $significance = \ITILEvent::INFORMATION) {

      // Only log if the event's level is the smae or lower than the setting from configuration
      if (!isset($input['level']) || !($input['level'] <= $CFG_GLPI["event_loglevel"])) {
         return false;
      }

      $input = [
         'name'      => $event,
         'content'   => json_encode([
            'type'      => $type,
            'items_id'  => intval($items_id),
            'service'   => $service,
            'level'     => $level
         ]),
         'significance' => $significance,
         'date'      => $_SESSION["glpi_currenttime"]
      ];

      // Drop after verifying ITILEvent logging works
      $tmp2 = new self();
      $tmp2->add($input);

      $tmp = new \ITILEvent();
      return $tmp->add($input);
   }


   /**
    * Clean old event - Call by cron
    *
    * @deprecated 10.0.0
    * @param $day integer
    *
    * @return integer number of events deleted
    * @todo Integrate log cleanup system into ITILEvent
   **/
   static function cleanOld($day) {
      global $DB;

      $secs = $day * DAY_TIMESTAMP;

      $result = $DB->delete(
         'glpi_events', [
            new \QueryExpression("UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs")
         ]
      );
      return $result->rowCount();
   }


   /**
    * Return arrays for function showEvent et lastEvent
    * @todo Convert to a translation system for ITILEvent data
   **/
   static function logArray() {

      static $logItemtype = [];
      static $logService  = [];

      if (count($logItemtype)) {
         return [$logItemtype, $logService];
      }

      $logItemtype = ['system'      => __('System'),
                           'devices'     => _n('Component', 'Components', Session::getPluralNumber()),
                           'planning'    => __('Planning'),
                           'reservation' => _n('Reservation', 'Reservations', Session::getPluralNumber()),
                           'dropdown'    => _n('Dropdown', 'Dropdowns', Session::getPluralNumber()),
                           'rules'       => _n('Rule', 'Rules', Session::getPluralNumber())];

      $logService = ['inventory'    => __('Assets'),
                          'tracking'     => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                          'maintain'     => __('Assistance'),
                          'planning'     => __('Planning'),
                          'tools'        => __('Tools'),
                          'financial'    => __('Management'),
                          'login'        => __('Connection'),
                          'setup'        => __('Setup'),
                          'security'     => __('Security'),
                          'reservation'  => _n('Reservation', 'Reservations', Session::getPluralNumber()),
                          'cron'         => _n('Automatic action', 'Automatic actions', Session::getPluralNumber()),
                          'document'     => _n('Document', 'Documents', Session::getPluralNumber()),
                          'notification' => _n('Notification', 'Notifications', Session::getPluralNumber()),
                          'plugin'       => _n('Plugin', 'Plugins', Session::getPluralNumber())];

      return [$logItemtype, $logService];
   }


   /**
    * @param $type
    * @param $items_id
   **/
   static function displayItemLogID($type, $items_id) {
      global $CFG_GLPI;

      //TODO Find uses for this function
      if (($items_id == "-1") || ($items_id == "0")) {
         echo "&nbsp;";//$item;
      } else {
         switch ($type) {
            case "rules" :
               echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/rule.generic.form.php?id=".
                     $items_id."\">".$items_id."</a>";
               break;

            case "infocom" :
               $rand = mt_rand();
               echo " <a href='#' onClick=\"".Html::jsGetElementbyID('infocom'.$rand).".
                       dialog('open');\">$items_id</a>";
               Ajax::createIframeModalWindow('infocom'.$rand,
                                             Infocom::getFormURLWithID($items_id),
                                             ['height' => 600]);

            case "devices" :
               echo $items_id;
               break;

            case "reservationitem" :
               echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/reservation.php?reservationitems_id=".
                     $items_id."\">".$items_id."</a>";
               break;

            default :
               $type = getSingular($type);
               $url  = '';
               if ($item = getItemForItemtype($type)) {
                  $url  =  $item->getFormURL();
               }
               if (!empty($url)) {
                  echo "<a href=\"".$url."?id=".$items_id."\">".$items_id."</a>";
               } else {
                  echo $items_id;
               }
               break;
         }
      }
   }


   /**
    * Print a nice tab for last event from inventory section
    *
    * Print a great tab to present lasts events occured on glpi
    *
    * @param $user   string  name user to search on message (default '')
    **/
   static function showForUser($user = "") {
      global $DB, $CFG_GLPI;

      //TODO Drop for functionality offered by Item_ITILEvent
      // Show events from $result in table form
      list($logItemtype, $logService) = self::logArray();

      // define default sorting
      $usersearch = "";
      if (!empty($user)) {
         $usersearch = $user." ";
      }

      // Query Database
      $iterator = $DB->request([
         'FROM'   => 'glpi_events',
         'WHERE'  => ['message' => ['LIKE', $usersearch . '%']],
         'ORDER'  => 'date DESC',
         'LIMIT'  => (int)$_SESSION['glpilist_limit']
      ]);

      // Number of results
      $number = count($iterator);;

      // No Events in database
      if ($number < 1) {
         echo "<br><div class='spaced'><table class='tab_cadrehov'>";
         echo "<tr><th>".__('No Event')."</th></tr>";
         echo "</table></div>";
         return;
      }

      // Output events
      $i = 0;

      echo "<br><div class='spaced'><table class='tab_cadre'>";
      echo "<tr><th colspan='5'>";
      //TRANS: %d is the number of item to display
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/event.php\">".
             sprintf(__('Last %d events'), $_SESSION['glpilist_limit'])."</a>";
      echo "</th></tr>";

      echo "<tr><th colspan='2'>".__('Source')."</th>";
      echo "<th>".__('Date')."</th>";
      echo "<th width='8%'>".__('Service')."</th>";
      echo "<th width='60%'>".__('Message')."</th></tr>";

      while ($data = $iterator->next()) {
         $ID       = $data['id'];
         $items_id = $data['items_id'];
         $type     = $data['type'];
         $date     = $data['date'];
         $service  = $data['service'];
         $message  = $data['message'];

         $itemtype = "&nbsp;";
         if (isset($logItemtype[$type])) {
            $itemtype = $logItemtype[$type];
         } else {
            $type = getSingular($type);
            if ($item = getItemForItemtype($type)) {
               $itemtype = $item->getTypeName(1);
            }
         }

         echo "<tr class='tab_bg_2'><td>".$itemtype."</td>";
         echo "<td class='center'>";
         self::displayItemLogID($type, $items_id);
         echo "</td><td class='center'>".Html::convDateTime($date)."</td>";
         echo "<td class='center'>".(isset($logService[$service])?$logService[$service]:'');
         echo "</td><td>".$message."</td></tr>";

         $i++;
      }

      echo "</table></div>";
   }


   /**
    * Print a nice tab for last event
    *
    * Print a great tab to present lasts events occured on glpi
    *
    * @param $target    where to go when complete
    * @param $order     order by clause occurences (eg: ) (default 'DESC')
    * @param $sort      order by clause occurences (eg: date) (defaut 'date')
    * @param $start     (default 0)
   **/
   static function showList($target, $order = 'DESC', $sort = 'date', $start = 0) {
      global $DB, $CFG_GLPI;

      //TODO Drop for functionality offered by Item_ITILEvent
      // Show events from $result in table form
      list($logItemtype, $logService) = self::logArray();

      // Columns of the Table
      $items = ["type"     => [__('Source'), ""],
                     "items_id" => [__('ID'), ""],
                     "date"     => [__('Date'), ""],
                     "service"  => [__('Service'), "width='8%'"],
                     "level"    => [__('Level'), "width='8%'"],
                     "message"  => [__('Message'), "width='50%'"]];

      // define default sorting
      if (!isset($items[$sort])) {
         $sort = "date";
      }
      if ($order != "ASC") {
         $order = "DESC";
      }

      // Query Database
      $iterator = $DB->request([
         'FROM'   => 'glpi_events',
         'ORDER'  => "$sort $order",
         'START'  => (int)$start,
         'LIMIT'  => (int)$_SESSION['glpilist_limit']
      ]);

      // Number of results
      $numrows = countElementsInTable("glpi_events");
      // Get results
      $number = count($iterator);

      // No Events in database
      if ($number < 1) {
         echo "<div class='center b'>".__('No Event')."</div>";
         return;
      }

      // Output events
      $i = 0;

      echo "<div class='center'>";
      $parameters = "sort=$sort&amp;order=$order";
      Html::printPager($start, $numrows, $target, $parameters);

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";

      foreach ($items as $field => $args) {
         echo "<th ".$args[1]."";
         if ($sort == $field) {
            echo " class='order_$order' ";
         }
         echo "><a href='$target?sort=$field&amp;order=".(($order=="ASC")?"DESC":"ASC")."'>".$args[0].
              "</a></th>";
      }
      echo "</tr>";

      while ($row = $iterator->next()) {
         $ID       = $row["id"];
         $items_id = $row["items_id"];
         $type     = $row["type"];
         $date     = $row["date"];
         $service  = $row["service"];
         $level    = $row["level"];
         $message  = $row["message"];

         $itemtype = "&nbsp;";
         if (isset($logItemtype[$type])) {
            $itemtype = $logItemtype[$type];
         } else {
            $type = getSingular($type);
            if ($item = getItemForItemtype($type)) {
               $itemtype = $item->getTypeName(1);
            }
         }

         echo "<tr class='tab_bg_2'>";
         echo "<td>$itemtype</td>";
         echo "<td class='center b'>";
         self::displayItemLogID($type, $items_id);
         echo "</td><td>".Html::convDateTime($date)."</td>";
         echo "<td class='center'>".(isset($logService[$service])?$logService[$service]:$service);
         echo "</td><td class='center'>".$level."</td><td>".$message."</td></tr>";

         $i++;
      }
      echo "</table></div><br>";
   }

   /**
    * Attempt to translate event properties
    * @since 10.0.0
    * @param array $properties
    * @return void
    */
   public static function translateEventProperties(array &$properties) {
      static $logItemtype = [];
      static $logService  = [];

      if (count($logItemtype)) {
         return [$logItemtype, $logService];
      }

      $logItemtype = ['system'      => __('System'),
                           'devices'     => _n('Component', 'Components', Session::getPluralNumber()),
                           'planning'    => __('Planning'),
                           'reservation' => _n('Reservation', 'Reservations', Session::getPluralNumber()),
                           'dropdown'    => _n('Dropdown', 'Dropdowns', Session::getPluralNumber()),
                           'rules'       => _n('Rule', 'Rules', Session::getPluralNumber())];

      $logService = ['inventory'    => __('Assets'),
                          'tracking'     => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                          'maintain'     => __('Assistance'),
                          'planning'     => __('Planning'),
                          'tools'        => __('Tools'),
                          'financial'    => __('Management'),
                          'login'        => __('Connection'),
                          'setup'        => __('Setup'),
                          'security'     => __('Security'),
                          'reservation'  => _n('Reservation', 'Reservations', Session::getPluralNumber()),
                          'cron'         => _n('Automatic action', 'Automatic actions', Session::getPluralNumber()),
                          'document'     => _n('Document', 'Documents', Session::getPluralNumber()),
                          'notification' => _n('Notification', 'Notifications', Session::getPluralNumber()),
                          'plugin'       => _n('Plugin', 'Plugins', Session::getPluralNumber())];

      if (array_key_exists('type', $properties)) {
         $properties['type']['name'] = __('Source');
         if (isset($properties['type']['value'])) {
            if (isset($logItemtype[$properties['type']['value']])) {
               $properties['type']['value'] = $logItemtype[$properties['type']['value']];
            } else {
               $type = getSingular($type);
               if ($item = getItemForItemtype($type)) {
                  $itemtype = $item->getTypeName(1);
                  $properties['type']['value'] = $itemtype;
               }
            }
         }
      }
      if (array_key_exists('service', $properties)) {
         $properties['service']['name'] = __('Service');
         if (isset($properties['service']['value'])) {
            if (isset($logService[$properties['service']['value']])) {
               $properties['service']['value'] = $logService[$properties['service']['value']];
            }
         }
      }
   }
}
