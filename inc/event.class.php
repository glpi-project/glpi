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

      Toolbox::deprecated('GLPI Events should be logged using the log function since version 10.0.0. Plugins should add events through ITILEvent.');
      // Deny any attempt to add an event to the legacy table
      return false;
   }

   function post_addItem() {
      //TODO For the sake of consistancy, logging alerts to a file should be dropped?
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
   static function log($items_id, $type, $level, $service, $event, $extrainfo = [], int $significance = \ITILEvent::INFORMATION) {
      global $CFG_GLPI;

      // Only log if the event's level is the same or lower than the setting from configuration
      if (!($level <= $CFG_GLPI["event_loglevel"])) {
         return false;
      }

      if (isset($extrainfo['_correlation_uuid'])) {
         $correlation_uuid = $extrainfo['_correlation_uuid'];
         unset($extrainfo['_correlation_uuid']);
      } else {
         $correlation_uuid = null;
      }

      $input = [
         'name'      => $event,
         'content'   => json_encode([
            'type'      => $type,
            'items_id'  => intval($items_id),
            'service'   => $service,
            'level'     => $level
         ] + $extrainfo),
         'significance' => $significance,
         'date'      => $_SESSION["glpi_currenttime"],
         'correlation_uuid'   => $correlation_uuid
      ];

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
    * Attempt to translate event properties
    * @since 10.0.0
    * @param array $properties
    * @return void
    */
   public static function translateEventProperties(array &$properties) {
      static $logItemtype = [];
      static $logService  = [];

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

      $otherFields = [
         'login_name'         => __('Login'),
         'level'              => __('Level'),
         'source_ip'          => __('Source IP'),
         'items_id'           => __('Items ID'),
         'itemtype'           => __('Item type'),
         'previous_revision'  => __('Previous revision'),
         'next_revision'      => __('Next revision'),
      ];

      if (array_key_exists('type', $properties)) {
         $properties['type']['name'] = __('Source');
         if (isset($properties['type']['value'])) {
            if (isset($logItemtype[$properties['type']['value']])) {
               $properties['type']['value'] = $logItemtype[$properties['type']['value']];
            } else {
               $type = getSingular($properties['type']['value']);
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

      foreach ($otherFields as $fieldname => $localname) {
         if (array_key_exists($fieldname, $properties)) {
            $properties[$fieldname]['name'] = $localname;
         }
      }
   }
}
